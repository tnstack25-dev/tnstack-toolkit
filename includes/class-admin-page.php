<?php
/**
 * WordPress admin page integration.
 *
 * @package TNStackToolkit
 */

namespace TNStack\Toolkit;

if (!defined('ABSPATH')) {
  exit;
}

final class Admin_Page
{
  private $active_dev_server = '';

  public function __construct()
  {
    add_action('admin_menu', [$this, 'add_admin_menu']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    add_action('admin_print_footer_scripts', [$this, 'print_react_refresh_preamble'], 1);
    add_filter('script_loader_tag', [$this, 'mark_script_as_module'], 10, 2);
  }

  public function add_admin_menu()
  {
    add_menu_page(
      'TNStack Toolkit',
      'TNStack Toolkit',
      'manage_options',
      'tnstack-toolkit',
      [$this, 'render_admin_page'],
      'dashicons-admin-generic',
      80
    );
  }

  public function enqueue_assets($hook)
  {
    if ($hook !== 'toplevel_page_tnstack-toolkit') {
      return;
    }

    $build_dir = TNSTACK_TOOLKIT_PATH . 'build/assets/';
    $dev_server = $this->get_dev_server_url();

    if ($dev_server && $this->is_dev_server_available($dev_server)) {
      $this->active_dev_server = $dev_server;

      wp_enqueue_script(
        'tnstack-toolkit-vite-client',
        $dev_server . '/@vite/client',
        [],
        null,
        true
      );

      wp_enqueue_script(
        'tnstack-toolkit',
        $dev_server . '/src/main.tsx',
        ['tnstack-toolkit-vite-client'],
        null,
        true
      );

      $this->localize_script();
      return;
    }

    $js_files = glob($build_dir . 'main-*.js');
    $css_files = glob($build_dir . '*.css');

    if (empty($js_files)) {
      error_log('TNStack Toolkit: Built JavaScript file not found.');
      return;
    }

    $js_file = basename($js_files[0]);
    $css_file = !empty($css_files) ? basename($css_files[0]) : '';

    wp_enqueue_script(
      'tnstack-toolkit',
      TNSTACK_TOOLKIT_URL . 'build/assets/' . $js_file,
      ['wp-element'],
      filemtime($build_dir . $js_file),
      true
    );

    if ($css_file) {
      wp_enqueue_style(
        'tnstack-toolkit',
        TNSTACK_TOOLKIT_URL . 'build/assets/' . $css_file,
        [],
        filemtime($build_dir . $css_file)
      );
    }

    $this->localize_script();
  }

  public function print_react_refresh_preamble()
  {
    if (!$this->active_dev_server) {
      return;
    }

    $refresh_url = wp_json_encode($this->active_dev_server . '/@react-refresh');

    echo '<script type="module">';
    echo 'import RefreshRuntime from ' . $refresh_url . ';';
    echo 'RefreshRuntime.injectIntoGlobalHook(window);';
    echo 'window.$RefreshReg$ = () => {};';
    echo 'window.$RefreshSig$ = () => (type) => type;';
    echo 'window.__vite_plugin_react_preamble_installed__ = true;';
    echo '</script>';
  }

  public function mark_script_as_module($tag, $handle)
  {
    if (!in_array($handle, ['tnstack-toolkit', 'tnstack-toolkit-vite-client'], true)) {
      return $tag;
    }

    if (preg_match('/\stype=(["\']).*?\1/', $tag)) {
      return preg_replace('/\stype=(["\']).*?\1/', ' type="module"', $tag, 1);
    }

    return str_replace('<script ', '<script type="module" ', $tag);
  }

  public function render_admin_page()
  {
    echo '<div id="tnstack-toolkit-root"></div>';
  }

  private function get_dev_server_url()
  {
    if (defined('TNSTACK_TOOLKIT_DEV_SERVER')) {
      return rtrim(TNSTACK_TOOLKIT_DEV_SERVER, '/');
    }

    if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
      return 'http://localhost:3000';
    }

    return '';
  }

  private function is_dev_server_available($dev_server)
  {
    $response = wp_remote_get($dev_server . '/@vite/client', [
      'timeout' => 0.2,
    ]);

    return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
  }

  private function localize_script()
  {
    wp_localize_script('tnstack-toolkit', 'TNStackToolkit', [
      'ajax_url'   => admin_url('admin-ajax.php'),
      'rest_url'   => rest_url(),
      'nonce'      => wp_create_nonce('wp_rest'),
      'settings_endpoint' => rest_url(Rest_API::NAMESPACE . Rest_API::ROUTE),
      'plugin_url' => TNSTACK_TOOLKIT_URL,
      'build_url'  => TNSTACK_TOOLKIT_URL . 'build/',
      'version'    => TNSTACK_TOOLKIT_VERSION,
    ]);
  }
}
