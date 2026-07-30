<?php
/**
 * JSON-LD Schema Markup (Article, Organization, Product, FAQ).
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'tnstack_schema_markup_output', 5 );

function tnstack_schema_markup_output() {
	if ( is_admin() ) {
		return;
	}

	$graphs = array();

	$graphs[] = tnstack_schema_organization();

	if ( is_singular( 'post' ) ) {
		$article = tnstack_schema_article();
		if ( $article ) {
			$graphs[] = $article;
		}
	}

	if ( is_singular( 'slim_product' ) ) {
		$product = tnstack_schema_slim_product();
		if ( $product ) {
			$graphs[] = $product;
		}
	}

	$faq = tnstack_schema_faq_from_content();
	if ( $faq ) {
		$graphs[] = $faq;
	}

	$graphs = array_filter( $graphs );

	if ( empty( $graphs ) ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array_values( $graphs ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

function tnstack_schema_organization() {
	return array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
		'logo'  => array(
			'@type' => 'ImageObject',
			'url'   => get_site_icon_url( 512 ) ?: '',
		),
	);
}

function tnstack_schema_article() {
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	$image = get_the_post_thumbnail_url( $post, 'full' );

	return array(
		'@type'            => 'Article',
		'@id'              => get_permalink( $post ) . '#article',
		'headline'         => get_the_title( $post ),
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $post->post_author ),
		),
		'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		'mainEntityOfPage' => get_permalink( $post ),
		'image'            => $image ?: null,
		'description'      => wp_strip_all_tags( get_the_excerpt( $post ) ),
	);
}

function tnstack_schema_slim_product() {
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	$price = get_post_meta( $post->ID, '_slim_price', true );
	$image = get_the_post_thumbnail_url( $post, 'full' );

	return array(
		'@type'       => 'Product',
		'@id'         => get_permalink( $post ) . '#product',
		'name'        => get_the_title( $post ),
		'image'       => $image ?: null,
		'description' => wp_strip_all_tags( get_the_excerpt( $post ) ),
		'offers'      => array(
			'@type'         => 'Offer',
			'price'         => $price ? (string) $price : '0',
			'priceCurrency' => 'VND',
			'availability'  => 'https://schema.org/InStock',
			'url'           => get_permalink( $post ),
		),
	);
}

function tnstack_schema_faq_from_content() {
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	$content = $post->post_content;
	if ( ! has_shortcode( $content, 'ttk_faq' ) && false === strpos( $content, 'ttk-faq-item' ) ) {
		return null;
	}

	preg_match_all( '/question="([^"]+)"[^]]*answer="([^"]+)"/', $content, $matches, PREG_SET_ORDER );

	if ( empty( $matches ) ) {
		return null;
	}

	$entities = array();
	foreach ( $matches as $match ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => html_entity_decode( $match[2], ENT_QUOTES, 'UTF-8' ),
			),
		);
	}

	return array(
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
}