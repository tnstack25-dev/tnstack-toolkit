<?php
/**
 * Disable comments site-wide.
 *
 * @package TNStackCore
 */

defined( 'ABSPATH' ) || exit;

if ( tnstack_core_feature_enabled( 'comments' ) ) {
	return;
}

function tnstack_core_disable_comments_globally() {
    // Disable comments for all content types
    function filter_media_comment_status( $open, $post_id ) {
        return false;
    }
    add_filter( 'comments_open', 'filter_media_comment_status', 10 , 2 );
    add_filter( 'pings_open', 'filter_media_comment_status', 10 , 2 );

    // Remove Comments menu from Admin area
    function remove_admin_comment_menus() {
        remove_menu_page( 'edit-comments.php' );
    }
    add_action( 'admin_menu', 'remove_admin_comment_menus' );

    // Redirect if user tries to access /wp-admin/edit-comments.php
    function redirect_comment_access() {
        global $pagenow;
        if ( $pagenow === 'edit-comments.php' ) {
            wp_safe_redirect( admin_url() );
            exit;
        }
    }
    add_action( 'admin_init', 'redirect_comment_access' );

    // Hide the 'Comments' column in post/page lists
    function remove_comment_columns( $columns ) {
        unset( $columns['comments'] );
        return $columns;
    }
    add_filter( 'manage_posts_columns', 'remove_comment_columns', 10, 1 );
    add_filter( 'manage_pages_columns', 'remove_comment_columns', 10, 1 );

    // Disable the comment box on the edit screen
    function disable_comment_status() {
        return false;
    }
    add_filter( 'get_default_comment_status', 'disable_comment_status', 10, 3 );
}
add_action( 'init', 'tnstack_core_disable_comments_globally' );
