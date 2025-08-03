<?php
/**
 * Plugin Name: Publication Management by devTarak
 * Plugin URI: https://github.com/devTarak/Publication-Management-Plugin
 * Description: A custom plugin to manage "Publication" custom post type with PDF uploads.
 * Version: 1.0
 * Author: Tarak Rahman
 * Author URI: https://devtarak.github.io/
 * License: GPL2
 * Text Domain: publication-management-by-devtarak
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit; 
// Define plugin paths
define( 'PM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include main logic
require_once PM_PLUGIN_PATH . 'includes/functions.php';
require_once PM_PLUGIN_PATH . 'includes/admin.php';
require_once PM_PLUGIN_PATH . 'includes/meta-boxes.php';

// Activate and deactivate hooks
register_activation_hook( __FILE__, 'pmdbt_activate_plugin' );
register_deactivation_hook( __FILE__, 'pmdbt_deactivate_plugin' );

function pmdbt_activate_plugin() {
    // Activation logic, e.g., custom database setup
}

function pmdbt_deactivate_plugin() {
    // Deactivation logic
}

