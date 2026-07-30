<?php
/**
 * Center images in post content.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'the_content', function ( $content ) {
    // Thêm class aligncenter nếu ảnh chưa có align
    $content = preg_replace(
        '/<img(?![^>]*(alignleft|alignright|aligncenter))/',
        '<img class="aligncenter"',
        $content
    );
    return $content;
});

