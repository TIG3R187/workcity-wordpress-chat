<?php
/**
 * Plugin Name: Workcity Chat
 * Description: A real-time chat plugin for buyers, merchants, designers, and agents. WooCommerce-integrated.
 * Version: 1.0
 * Author: Isaac Yakubu
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include plugin files
require_once plugin_dir_path(__FILE__) . 'includes/installer.php';
require_once plugin_dir_path(__FILE__) . 'includes/cpt-chat-session.php';
require_once plugin_dir_path(__FILE__) . 'includes/chat-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/rest-api.php';

// Run install on activation
register_activation_hook(__FILE__, 'workcity_chat_install');

/**
 * Enqueue assets
 */
function workcity_chat_enqueue_assets() {
    // Styles
    wp_enqueue_style('workcity-chat-css', plugin_dir_url(__FILE__) . 'assets/css/chat.css', array(), '1.0');

    // Scripts
    wp_enqueue_script('workcity-chat-js', plugin_dir_url(__FILE__) . 'assets/js/chat.js', array('jquery'), '1.0', true);
    wp_enqueue_script('workcity-product-chat', plugin_dir_url(__FILE__) . 'assets/js/product-chat.js', array('jquery'), '1.0', true);

    // Localize
    wp_localize_script('workcity-chat-js', 'workcityChatData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('workcity_chat_nonce'),
        'current_user_id' => get_current_user_id()
    ));
}
add_action('wp_enqueue_scripts', 'workcity_chat_enqueue_assets');
