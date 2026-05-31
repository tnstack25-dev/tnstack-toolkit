<?php
/**
 * Plugin uninstall handler.
 *
 * @package TNStackToolkit
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

delete_option('tnstack_toolkit_settings');
