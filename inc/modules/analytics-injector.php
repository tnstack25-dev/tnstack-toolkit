<?php
/**
 * Google Analytics / GTM injection.
 *
 * @package TNStackToolkit
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'tnstack_analytics_head', 1 );
function tnstack_analytics_defaults() {
	return array(
		'gtm_id' => '',
		'ga4_id' => '',
	);
}

function tnstack_analytics_settings() {
	return tnstack_module_get_settings( 'analytics-injector', tnstack_analytics_defaults() );
}

function tnstack_analytics_render_admin() {
	if ( ! current_user_can( TNStack_Account_Permissions::MANAGE_CAP ) ) {
		return;
	}

	if ( isset( $_POST['tnstack_analytics_save'] ) ) {
		check_admin_referer( 'tnstack_analytics' );
		tnstack_module_update_settings(
			'analytics-injector',
			array(
				'gtm_id' => sanitize_text_field( wp_unslash( $_POST['gtm_id'] ?? '' ) ),
				'ga4_id' => sanitize_text_field( wp_unslash( $_POST['ga4_id'] ?? '' ) ),
			),
			tnstack_analytics_defaults()
		);
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Đã lưu.', 'tnstack-toolkit' ) . '</p></div>';
	}

	$s = tnstack_analytics_settings();
	?>
	<div class="wrap"><h1><?php esc_html_e( 'Analytics / GTM', 'tnstack-toolkit' ); ?></h1>
	<form method="post"><?php wp_nonce_field( 'tnstack_analytics' ); ?>
	<table class="form-table">
		<tr><th>GTM ID</th><td><input name="gtm_id" class="regular-text" value="<?php echo esc_attr( $s['gtm_id'] ); ?>" placeholder="GTM-XXXXXXX" /></td></tr>
		<tr><th>GA4 ID</th><td><input name="ga4_id" class="regular-text" value="<?php echo esc_attr( $s['ga4_id'] ); ?>" placeholder="G-XXXXXXXX" /></td></tr>
	</table><?php submit_button( __( 'Lưu', 'tnstack-toolkit' ), 'primary', 'tnstack_analytics_save' ); ?></form></div>
	<?php
}

function tnstack_analytics_head() {
	if ( is_admin() ) {
		return;
	}

	$s = tnstack_analytics_settings();

	if ( $s['gtm_id'] ) {
		$id = esc_attr( $s['gtm_id'] );
		echo "<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$id}');</script>\n";
	}

	if ( $s['ga4_id'] && ! $s['gtm_id'] ) {
		$id = esc_attr( $s['ga4_id'] );
		echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$id}\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$id}');</script>\n";
	}
}
