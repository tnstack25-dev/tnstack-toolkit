<?php
/**
 * Main plugin orchestration.
 *
 * @package TNStackToolkit
 */

namespace TNStack\Toolkit;

if (!defined('ABSPATH')) {
  exit;
}

final class Plugin
{
  private static $instance;

  private $admin_page;

  private $rest_api;

  private $updater;

  private function __construct()
  {
    $this->admin_page = new Admin_Page();
    $this->rest_api = new Rest_API();
    $this->updater = new Updater();
  }

  public static function run()
  {
    if (!self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }
}
