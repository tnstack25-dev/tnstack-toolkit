<?php
/**
 * Plugin settings storage.
 *
 * @package TNStackToolkit
 */

namespace TNStack\Toolkit;

if (!defined('ABSPATH')) {
  exit;
}

final class Settings
{
  const OPTION_NAME = 'tnstack_toolkit_settings';

  public static function get_defaults()
  {
    return [
      'title'      => 'TNStack Toolkit',
      'is_enabled' => false,
    ];
  }

  public static function get()
  {
    $settings = get_option(self::OPTION_NAME, []);

    if (!is_array($settings)) {
      $settings = [];
    }

    return self::sanitize(array_merge(self::get_defaults(), $settings));
  }

  public static function update($settings)
  {
    $settings = self::sanitize($settings);
    update_option(self::OPTION_NAME, $settings);

    return $settings;
  }

  public static function sanitize($settings)
  {
    $settings = is_array($settings) ? $settings : [];

    return [
      'title'      => sanitize_text_field($settings['title'] ?? ''),
      'is_enabled' => rest_sanitize_boolean($settings['is_enabled'] ?? false),
    ];
  }
}
