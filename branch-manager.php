<?php
/**
 * Plugin Name: Branch Manager
 * Description: Manage and display company branches with map + grid integration.
 * Version: 1.3
 * Author: Modern Chameleon Digital
 * Author URI: https://modernchameleonph.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // No direct access

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/post-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('leaflet-css', plugin_dir_url(__FILE__) . 'assets/leaflet.css');
    wp_enqueue_script('leaflet-js', plugin_dir_url(__FILE__) . 'assets/leaflet.js', [], null, true);
});