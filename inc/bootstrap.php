<?php
/**
 * Plugin bootstrap entry.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

require_once TNSTACK_TOOLKIT_PATH . 'inc/core/class-plugin.php';
require_once TNSTACK_TOOLKIT_PATH . 'inc/core/class-lifecycle.php';

TNStack_Plugin::instance()->register();
