<?php
/*
Plugin Name: FunkyTiles
Plugin URI:  https://github.com/dusky101/Funky-Tiles/
Description: Dynamically integrate tiles into WordPress pages.
Version: 1.1
Author: TechWhisperers PE
Author URI:  https://techwhispererspod.com/wordpress-plugins
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin version and paths
define('FUNKYTILES_VERSION', '1.1');
define('FUNKYTILES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FUNKYTILES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FUNKYTILES_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include necessary files
require_once FUNKYTILES_PLUGIN_DIR . 'admin/admin-page.php';
require_once FUNKYTILES_PLUGIN_DIR . 'admin/admin-html.php';
require_once FUNKYTILES_PLUGIN_DIR . 'includes/shortcode.php';

// Conditionally enqueue scripts and styles for admin and front-end
function funkytiles_enqueue_assets($hook_suffix) {
    // Admin scripts and styles
    if ($hook_suffix === 'toplevel_page_funkytiles-settings') {
        wp_enqueue_script('funkytiles-admin', FUNKYTILES_PLUGIN_URL . 'js/admin.js', ['jquery', 'wp-color-picker'], FUNKYTILES_VERSION, true);
        wp_enqueue_style('funkytiles-admin-style', FUNKYTILES_PLUGIN_URL . 'css/funkytiles-admin-style.css', [], FUNKYTILES_VERSION);
        wp_enqueue_style('wp-color-picker');

        wp_localize_script('funkytiles-admin', 'funkytiles_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('funkytiles_nonce'),
        ]);
    }

    // Front-end scripts and styles
   // wp_enqueue_style('funkytiles-style', FUNKYTILES_PLUGIN_URL . 'css/style.css', [], FUNKYTILES_VERSION);
    //wp_enqueue_script('funkytiles-frontend', FUNKYTILES_PLUGIN_URL . 'js/frontend.js', ['jquery'], FUNKYTILES_VERSION, true);
}
add_action('admin_enqueue_scripts', 'funkytiles_enqueue_assets');
add_action('wp_enqueue_scripts', 'funkytiles_enqueue_assets');
// Register Settings
function funkytiles_register_settings() {
    register_setting('funkytiles', 'funkytiles_tiles', 'funkytiles_sanitize_tiles');
    register_setting('funkytiles', 'funkytiles_categories', 'funkytiles_sanitize_categories');
}

add_action('admin_init', 'funkytiles_register_settings');

// Sanitization callbacks
function funkytiles_sanitize_tiles($tiles) {
    if (is_array($tiles)) {
        foreach ($tiles as &$tile) {
            $tile = array_map('sanitize_text_field', $tile);
            $tile['image'] = esc_url_raw($tile['image']);
            $tile['link_url'] = esc_url_raw($tile['link_url']);
        }
    }
    return $tiles;
}

function funkytiles_sanitize_categories($categories) {
    foreach ($categories as $category_name => &$styles) {
        $styles = array_map('sanitize_hex_color', $styles);
        $styles['font_family'] = sanitize_text_field($styles['font_family']);
    }
    return $categories;
}

// Register menu page for the plugin settings
function funkytiles_add_admin_menu() {
    add_menu_page(
        __('FunkyTiles Settings', 'funkytiles'),
        __('FunkyTiles', 'funkytiles'),
        'manage_options',
        'funkytiles-settings',
        'funkytiles_admin_html',
        'dashicons-layout'
    );
}

add_action('admin_menu', 'funkytiles_add_admin_menu');

// Settings page content callback
function funkytiles_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    include FUNKYTILES_PLUGIN_DIR . 'admin/admin-html.php';
}

function funkytiles_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=funkytiles-settings') . '">' . __('Settings', 'funkytiles') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'funkytiles_add_settings_link');
