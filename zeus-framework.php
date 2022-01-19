<?php

/**
 * Plugin Name: Zeus Framework
 * Plugin URI: https://brunopolo.com.br/
 * Description: The framework for WP plugin creators
 * Version: 1.0.0
 * Author: Bruno Polo
 * Author URI: https://brunopolo.com.br
 * Text Domain: zeus-framework
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.0
 *
 * @package Zeus
 */

defined('ABSPATH') || exit;

require_once "vendor/autoload.php";

zeus()->run();
