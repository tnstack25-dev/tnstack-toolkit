<?php
/**
 * GitHub Releases plugin updater.
 *
 * @package TNStackToolkit
 */

namespace TNStack\Toolkit;

if (!defined('ABSPATH')) {
  exit;
}

final class Updater
{
  const ASSET_NAME = 'tnstack-toolkit.zip';
  const CACHE_KEY = 'tnstack_toolkit_github_release';
  const REPOSITORY_URL = 'https://github.com/tnstack25-dev/tnstack-toolkit';
  const RELEASE_API_URL = 'https://api.github.com/repos/tnstack25-dev/tnstack-toolkit/releases/latest';
  const SLUG = 'tnstack-toolkit';
  const UPDATE_URI = 'https://github.com/tnstack25-dev/tnstack-toolkit';

  public function __construct()
  {
    add_filter('update_plugins_github.com', [$this, 'check_for_update'], 10, 4);
    add_filter('plugins_api', [$this, 'get_plugin_information'], 20, 3);
    add_action('upgrader_process_complete', [$this, 'clear_cache'], 10, 2);
  }

  public function check_for_update($update, $plugin_data, $plugin_file, $locales)
  {
    unset($locales);

    if ($plugin_file !== plugin_basename(TNSTACK_TOOLKIT_FILE)) {
      return $update;
    }

    $release = $this->get_release();

    if (!$release) {
      return false;
    }

    $installed_version = (string) ($plugin_data['Version'] ?? TNSTACK_TOOLKIT_VERSION);

    if (version_compare($release['version'], $installed_version, '<=')) {
      return false;
    }

    return [
      'id'           => self::UPDATE_URI,
      'slug'         => self::SLUG,
      'version'      => $release['version'],
      'url'          => self::REPOSITORY_URL,
      'package'      => $release['package'],
      'requires'     => '6.6',
      'requires_php' => '8.0',
    ];
  }

  public function get_plugin_information($result, $action, $args)
  {
    if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== self::SLUG) {
      return $result;
    }

    $release = $this->get_release();

    if (!$release) {
      return $result;
    }

    return (object) [
      'name'          => 'TNStack Toolkit',
      'slug'          => self::SLUG,
      'version'       => $release['version'],
      'author'        => '<a href="https://tnstack.com/">TNStack</a>',
      'homepage'      => self::REPOSITORY_URL,
      'download_link' => $release['package'],
      'requires'      => '6.6',
      'requires_php'  => '8.0',
      'last_updated'  => $release['published_at'],
      'sections'      => [
        'description' => 'A WordPress administration toolkit by TNStack.',
        'changelog'   => wp_kses_post(wpautop($release['body'])),
      ],
    ];
  }

  public function clear_cache($upgrader, $options)
  {
    unset($upgrader);

    if (($options['action'] ?? '') === 'update' && ($options['type'] ?? '') === 'plugin') {
      delete_site_transient(self::CACHE_KEY);
    }
  }

  private function get_release()
  {
    $cached_release = get_site_transient(self::CACHE_KEY);

    if (is_array($cached_release)) {
      return $cached_release;
    }

    $response = wp_remote_get(self::RELEASE_API_URL, [
      'headers' => [
        'Accept'     => 'application/vnd.github+json',
        'User-Agent' => 'TNStack-Toolkit/' . TNSTACK_TOOLKIT_VERSION,
      ],
      'timeout' => 10,
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
      return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $package = $this->find_package_url($data['assets'] ?? []);
    $version = ltrim((string) ($data['tag_name'] ?? ''), 'vV');

    if (!$package || !$version || !preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
      return false;
    }

    $release = [
      'body'         => (string) ($data['body'] ?? ''),
      'package'      => $package,
      'published_at' => (string) ($data['published_at'] ?? ''),
      'version'      => $version,
    ];

    set_site_transient(self::CACHE_KEY, $release, HOUR_IN_SECONDS);

    return $release;
  }

  private function find_package_url($assets)
  {
    foreach ($assets as $asset) {
      if (($asset['name'] ?? '') === self::ASSET_NAME && !empty($asset['browser_download_url'])) {
        return esc_url_raw($asset['browser_download_url']);
      }
    }

    return '';
  }
}
