<?php
/**
 * Contact information block.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

$contact = slim_catalog_get_contact_details();

if ( empty( $contact['hotline'] ) && empty( $contact['zalo'] ) && empty( $contact['email'] ) && empty( $contact['addresses'] ) ) {
	return;
}
?>
<div class="sc-contact">
	<h3 class="sc-contact__title"><?php esc_html_e( 'Contact Information', 'slim-catalog' ); ?></h3>
	<ul class="sc-contact__list">
		<?php if ( ! empty( $contact['hotline'] ) ) : ?>
			<li class="sc-contact__item">
				<span class="sc-contact__label"><?php esc_html_e( 'Hotline', 'slim-catalog' ); ?></span>
				<a class="sc-contact__value" href="<?php echo esc_url( $contact['hotline_url'] ); ?>"><?php echo esc_html( $contact['hotline_display'] ); ?></a>
			</li>
		<?php endif; ?>

		<?php if ( ! empty( $contact['zalo'] ) ) : ?>
			<li class="sc-contact__item">
				<span class="sc-contact__label"><?php esc_html_e( 'Zalo', 'slim-catalog' ); ?></span>
				<a class="sc-contact__value" href="<?php echo esc_url( $contact['zalo_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $contact['zalo'] ); ?></a>
			</li>
		<?php endif; ?>

		<?php if ( ! empty( $contact['email'] ) ) : ?>
			<li class="sc-contact__item">
				<span class="sc-contact__label"><?php esc_html_e( 'Email', 'slim-catalog' ); ?></span>
				<a class="sc-contact__value" href="mailto:<?php echo esc_attr( $contact['email'] ); ?>"><?php echo esc_html( $contact['email'] ); ?></a>
			</li>
		<?php endif; ?>

		<?php if ( ! empty( $contact['addresses'] ) ) : ?>
			<li class="sc-contact__item">
				<span class="sc-contact__label"><?php esc_html_e( 'Address', 'slim-catalog' ); ?></span>
				<ul class="sc-contact__addresses">
					<?php foreach ( $contact['addresses'] as $address ) : ?>
						<li class="sc-contact__value sc-contact__value--text sc-contact__address"><?php echo esc_html( $address ); ?></li>
					<?php endforeach; ?>
				</ul>
			</li>
		<?php endif; ?>
	</ul>
</div>
