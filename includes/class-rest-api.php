<?php
/**
 * Plugin REST API endpoints.
 *
 * @package TNStackToolkit
 */

namespace TNStack\Toolkit;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
  exit;
}

final class Rest_API
{
  const NAMESPACE = 'tnstack-toolkit/v1';
  const ROUTE = '/settings';

  public function __construct()
  {
    add_action('rest_api_init', [$this, 'register_routes']);
  }

  public function register_routes()
  {
    register_rest_route(
      self::NAMESPACE,
      self::ROUTE,
      [
        [
          'methods'             => 'GET',
          'callback'            => [$this, 'get_settings'],
          'permission_callback' => [$this, 'can_manage_settings'],
        ],
        [
          'methods'             => 'POST',
          'callback'            => [$this, 'update_settings'],
          'permission_callback' => [$this, 'can_manage_settings'],
          'args'                => [
            'title' => [
              'type'              => 'string',
              'required'          => true,
              'sanitize_callback' => 'sanitize_text_field',
            ],
            'is_enabled' => [
              'type'              => 'boolean',
              'required'          => true,
              'sanitize_callback' => 'rest_sanitize_boolean',
            ],
          ],
        ],
      ]
    );
  }

  public function can_manage_settings()
  {
    return current_user_can('manage_options');
  }

  public function get_settings()
  {
    return new WP_REST_Response(Settings::get(), 200);
  }

  public function update_settings(WP_REST_Request $request)
  {
    $settings = Settings::update([
      'title'      => $request->get_param('title'),
      'is_enabled' => $request->get_param('is_enabled'),
    ]);

    return new WP_REST_Response($settings, 200);
  }
}
