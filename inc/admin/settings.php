<?php
/**
 * Backward-compatible settings loader.
 *
 * @package TNStackToolkit
 * @deprecated 2.0.0 Settings now live in inc/core/site-settings.php.
 */

defined( 'ABSPATH' ) || exit;

require_once tnstack_core_path( 'inc/core/site-settings.php' );
