<?php
/**
 * Convert image uploads to WebP and serve WebP on the frontend.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'wp_generate_attachment_metadata', 'tnstack_webp_convert_attachment', 10, 2 );
add_filter( 'wp_get_attachment_url', 'tnstack_webp_filter_attachment_url', 10, 2 );
add_filter( 'wp_get_attachment_image_src', 'tnstack_webp_filter_attachment_image_src', 10, 4 );
add_filter( 'wp_calculate_image_srcset', 'tnstack_webp_filter_image_srcset', 10, 5 );
add_filter( 'the_content', 'tnstack_webp_filter_content_images', 25 );

add_filter( 'wp_content_img_tag', 'tnstack_webp_filter_img_tag', 10, 3 );

/**
 * @param array<string, mixed> $metadata      Attachment metadata.
 * @param int                  $attachment_id Attachment ID.
 * @return array<string, mixed>
 */
function tnstack_webp_convert_attachment( $metadata, $attachment_id ) {
	if ( ! tnstack_webp_supported() || empty( $metadata['file'] ) ) {
		return $metadata;
	}

	$upload_dir = wp_upload_dir();
	$base_dir   = trailingslashit( $upload_dir['basedir'] );
	$dirname    = trailingslashit( dirname( $metadata['file'] ) );

	tnstack_webp_convert_file( $base_dir . $metadata['file'] );

	if ( ! empty( $metadata['original_image'] ) ) {
		tnstack_webp_convert_file( $base_dir . $dirname . $metadata['original_image'] );
	}

	if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $size ) {
			if ( ! empty( $size['file'] ) ) {
				tnstack_webp_convert_file( $base_dir . $dirname . $size['file'] );
			}
		}
	}

	return $metadata;
}

/**
 * @param string $url           Attachment URL.
 * @param int    $attachment_id Attachment ID.
 * @return string
 */
function tnstack_webp_filter_attachment_url( $url, $attachment_id ) {
	unset( $attachment_id );

	return tnstack_webp_resolve_url( $url ) ?: $url;
}

/**
 * @param array<int, mixed>|false $image         Image data.
 * @param int                     $attachment_id Attachment ID.
 * @param string|int[]            $size          Image size.
 * @param bool                    $icon          Icon flag.
 * @return array<int, mixed>|false
 */
function tnstack_webp_filter_attachment_image_src( $image, $attachment_id, $size, $icon ) {
	unset( $attachment_id, $size, $icon );

	if ( ! is_array( $image ) || empty( $image[0] ) ) {
		return $image;
	}

	$webp_url = tnstack_webp_resolve_url( $image[0] );
	if ( $webp_url ) {
		$image[0] = $webp_url;
	}

	return $image;
}

/**
 * @param array<int, array<string, mixed>>|false $sources       Srcset sources.
 * @param array<int, int>                        $size_array    Width and height.
 * @param string                                 $image_src     Main image URL.
 * @param array<string, mixed>                   $image_meta    Attachment metadata.
 * @param int                                    $attachment_id Attachment ID.
 * @return array<int, array<string, mixed>>|false
 */
function tnstack_webp_filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	unset( $size_array, $image_src, $image_meta, $attachment_id );

	if ( ! is_array( $sources ) ) {
		return $sources;
	}

	foreach ( $sources as $width => $source ) {
		if ( empty( $source['url'] ) ) {
			continue;
		}

		$webp_url = tnstack_webp_resolve_url( $source['url'] );
		if ( $webp_url ) {
			$sources[ $width ]['url'] = $webp_url;
		}
	}

	return $sources;
}

/**
 * @param string $content Post content.
 * @return string
 */
function tnstack_webp_filter_content_images( $content ) {
	if ( is_admin() || ! tnstack_webp_should_serve() || false === stripos( $content, 'wp-content/uploads/' ) ) {
		return $content;
	}

	return tnstack_webp_replace_urls_in_html( $content );
}

/**
 * @param string $filtered_image Full img tag.
 * @param string $context        Context.
 * @param int    $attachment_id  Attachment ID.
 * @return string
 */
function tnstack_webp_filter_img_tag( $filtered_image, $context, $attachment_id ) {
	unset( $context, $attachment_id );

	if ( ! tnstack_webp_should_serve() ) {
		return $filtered_image;
	}

	return tnstack_webp_replace_urls_in_html( $filtered_image );
}

/**
 * @param string $html HTML fragment.
 * @return string
 */
function tnstack_webp_replace_urls_in_html( $html ) {
	return (string) preg_replace_callback(
		'#((?:https?:)?//[^"\'\s>]+\.(?:jpe?g|png))#i',
		static function ( $matches ) {
			return tnstack_webp_resolve_url( $matches[1] ) ?: $matches[1];
		},
		$html
	);
}

/**
 * @return bool
 */
function tnstack_webp_supported() {
	if ( ! function_exists( 'imagewebp' ) || ! function_exists( 'imagecreatefromjpeg' ) || ! function_exists( 'imagecreatefrompng' ) ) {
		return false;
	}

	if ( function_exists( 'gd_info' ) ) {
		$gd = gd_info();
		return ! empty( $gd['WebP Support'] );
	}

	return true;
}

/**
 * @return bool
 */
function tnstack_webp_should_serve() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}

	$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? (string) wp_unslash( $_SERVER['HTTP_ACCEPT'] ) : '';

	return false !== stripos( $accept, 'image/webp' );
}

/**
 * @param string $url Image URL.
 * @return string
 */
function tnstack_webp_resolve_url( $url ) {
	if ( ! tnstack_webp_should_serve() ) {
		return '';
	}

	$webp_url = preg_replace( '/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url );
	if ( $webp_url === $url ) {
		return '';
	}

	$webp_path = tnstack_webp_url_to_path( $webp_url );
	if ( $webp_path && is_readable( $webp_path ) && filesize( $webp_path ) > 0 ) {
		return $webp_url;
	}

	return '';
}

/**
 * @param string $url Upload URL.
 * @return string
 */
function tnstack_webp_url_to_path( $url ) {
	$upload  = wp_upload_dir();
	$baseurl = $upload['baseurl'];
	$basedir = $upload['basedir'];

	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		$url = site_url( $url );
	}

	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( '' === $path ) {
		return '';
	}

	if ( 0 === strpos( $url, $baseurl ) ) {
		return $basedir . substr( $path, strlen( (string) wp_parse_url( $baseurl, PHP_URL_PATH ) ) );
	}

	return '';
}

/**
 * @param string $file_path Absolute file path.
 */
function tnstack_webp_convert_file( $file_path ) {
	if ( ! is_readable( $file_path ) ) {
		return;
	}

	$type = wp_check_filetype( $file_path );
	if ( ! in_array( $type['type'], array( 'image/jpeg', 'image/png' ), true ) ) {
		return;
	}

	$webp_path = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file_path );
	if ( $webp_path === $file_path ) {
		return;
	}

	$image = null;
	if ( 'image/jpeg' === $type['type'] ) {
		$image = imagecreatefromjpeg( $file_path );
	} elseif ( 'image/png' === $type['type'] ) {
		$image = imagecreatefrompng( $file_path );
		if ( $image ) {
			imagepalettetotruecolor( $image );
			imagealphablending( $image, true );
			imagesavealpha( $image, true );
		}
	}

	if ( ! $image ) {
		return;
	}

	$converted = imagewebp( $image, $webp_path, 82 );
	imagedestroy( $image );

	if ( ! $converted || ! is_readable( $webp_path ) || 0 === filesize( $webp_path ) ) {
		if ( is_file( $webp_path ) ) {
			wp_delete_file( $webp_path );
		}
	}
}