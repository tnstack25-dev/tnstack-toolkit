<?php
/**
 * Plugin Name:       TNStack Toolkit
 * Plugin URI:        https://tnstack.com/
 * Description:       A WordPress administration toolkit by TNStack.
 * Version:           0.0.1
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Author:            TNStack
 * Author URI:        https://tnstack.com/
 * Update URI:        https://github.com/tnstack25-dev/tnstack-toolkit
 * Text Domain:       tnstack-toolkit
 * Domain Path:       /languages
 * License:           MIT
 * License URI:       https://opensource.org/license/mit
 */

if (!defined('ABSPATH')) {
  exit;
}

define('TNSTACK_TOOLKIT_VERSION', '0.0.1');
define('TNSTACK_TOOLKIT_FILE', __FILE__);
define('TNSTACK_TOOLKIT_PATH', plugin_dir_path(__FILE__));
define('TNSTACK_TOOLKIT_URL', plugin_dir_url(__FILE__));

require_once TNSTACK_TOOLKIT_PATH . 'includes/class-admin-page.php';
require_once TNSTACK_TOOLKIT_PATH . 'includes/class-settings.php';
require_once TNSTACK_TOOLKIT_PATH . 'includes/class-rest-api.php';
require_once TNSTACK_TOOLKIT_PATH . 'includes/class-updater.php';
require_once TNSTACK_TOOLKIT_PATH . 'includes/class-plugin.php';

\TNStack\Toolkit\Plugin::run();
