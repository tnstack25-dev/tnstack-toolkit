<?php
/**
 * Vietnamese localization for Slim Catalog UI.
 *
 * @package SlimCatalog
 */

defined( 'ABSPATH' ) || exit;

class Slim_Catalog_I18n {

	/**
	 * @var array<string, string>|null
	 */
	private static $strings = null;

	public static function init() {
		add_filter( 'gettext', array( __CLASS__, 'translate' ), 10, 3 );
		add_filter( 'ngettext', array( __CLASS__, 'translate_plural' ), 10, 5 );
	}

	/**
	 * @return array<string, string>
	 */
	private static function strings() {
		if ( null === self::$strings ) {
			$file = SLIM_CATALOG_PATH . 'languages/vi-strings.php';
			self::$strings = is_readable( $file ) ? (array) include $file : array();
		}

		return self::$strings;
	}

	/**
	 * @param string $translated Translated text.
	 * @param string $text       Original text.
	 * @param string $domain     Text domain.
	 * @return string
	 */
	public static function translate( $translated, $text, $domain ) {
		if ( 'slim-catalog' !== $domain ) {
			return $translated;
		}

		return self::translate_string( $text, $translated );
	}

	/**
	 * @param string $text       Original text.
	 * @param string $translated Fallback translation.
	 * @return string
	 */
	public static function translate_string( $text, $translated = '' ) {
		$strings = self::strings();

		return $strings[ $text ] ?? ( '' !== $translated ? $translated : $text );
	}

	/**
	 * @param string $translated Translated text.
	 * @param string $single     Singular form.
	 * @param string $plural     Plural form.
	 * @param int    $number     Number.
	 * @param string $domain     Text domain.
	 * @return string
	 */
	public static function translate_plural( $translated, $single, $plural, $number, $domain ) {
		if ( 'slim-catalog' !== $domain ) {
			return $translated;
		}

		$strings = self::strings();
		$key     = 1 === (int) $number ? $single : $plural;

		return $strings[ $key ] ?? $translated;
	}

	/**
	 * Localize headings loaded from Flatsome builder option files.
	 *
	 * @param array<string, mixed>|false $options Builder options.
	 * @return array<string, mixed>|false
	 */
	public static function localize_builder_options( $options ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}

		$strings = self::strings();

		foreach ( $options as $key => $value ) {
			if ( is_array( $value ) ) {
				$options[ $key ] = self::localize_builder_options( $value );
				continue;
			}

			if ( is_string( $value ) && '' !== $value && isset( $strings[ $value ] ) ) {
				$options[ $key ] = $strings[ $value ];
			}
		}

		return $options;
	}
}