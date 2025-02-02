<?php
/*
Plugin Name: Spotlight Search for WP
Description: Powerful Search, Smart Shortcuts, and Seamless Integration with Your WordPress Workflow, triggered by Shift + /
Version: 1.7
Author: Ch Jaswanth
Author URI: https://chjaswanth.com
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin URLs
define('ESSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ESSP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Function to get settings
function essp_get_settings() {
    $defaults = array(
        'post_types'         => array('post', 'page'),
        'default_builder'    => 'default',
        'default_post_type'  => 'page',
        'enable_admin_pages' => 0,
        'results_order'      => 'content_first',
    );

    $settings = get_option('essp_settings', $defaults);
    $settings = wp_parse_args($settings, $defaults);

    return $settings;
}

// Include required files
require_once ESSP_PLUGIN_DIR . 'includes/admin-pages.php'; // For essp_get_admin_pages()
require_once ESSP_PLUGIN_DIR . 'includes/admin-pages-settings.php'; // For essp_render_admin_pages_settings()
require_once ESSP_PLUGIN_DIR . 'includes/settings-page.php';
require_once ESSP_PLUGIN_DIR . 'includes/search-handler.php';

// Enqueue scripts and styles on both frontend and backend for admin users only
add_action('wp_enqueue_scripts', 'essp_enqueue_assets');
add_action('admin_enqueue_scripts', 'essp_enqueue_assets');
function essp_enqueue_assets() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style('essp-style', ESSP_PLUGIN_URL . 'css/spotlight-style.css');

    // Enqueue JS
    wp_enqueue_script('essp-script', ESSP_PLUGIN_URL . 'js/spotlight-script.js', array(), null, true);

    // Localize script to pass AJAX URL and nonce
    wp_localize_script('essp-script', 'essp_ajax_object', array(
        'ajax_url'               => admin_url('admin-ajax.php'),
        'essp_nonce'             => wp_create_nonce('essp_nonce'),
        'essp_settings'          => essp_get_settings(),
        'current_user_can_create'=> current_user_can('edit_posts') ? true : false,
        'is_admin_user'          => current_user_can('manage_options') ? true : false,
    ));
}

// AJAX handler for search
add_action('wp_ajax_essp_search', 'essp_search_handler');


// Add settings pages
add_action('admin_menu', 'essp_add_settings_pages');


//feature requests
// Export Quick Admin Nav Pages & Option to import
// 