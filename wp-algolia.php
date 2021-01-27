<?php

/**
 * Plugin Name:       Algolia
 * Plugin URI:        https://www.algolia.com/
 * Description:       A (unofficial) WordPress plugin to use Algolia as a search engine.
 * Version:           3.1.2
 * Author:            Léo Colombaro
 * Author URI:        https://colombaro.fr/
 * License:           MIT License
 * License URI:       https://opensource.org/licenses/MIT
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Load dependencies
if (!class_exists('Algolia_Helper')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// The Algolia Search plugin version
define('ALGOLIA_VERSION', '3.1.2');

Algolia_Helper::get_instance();

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('algolia', \Algolia_CLI::class);
}
