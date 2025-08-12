
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
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php'; // Using fixed version
require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/rest-api.php';

// Run install on activation
register_activation_hook(__FILE__, 'workcity_chat_install');

/**
 * Enqueue assets
 */
function workcity_chat_enqueue_assets() {
    // Styles - using fixed CSS
    wp_enqueue_style('workcity-chat-css', plugin_dir_url(__FILE__) . 'assets/css/chat-fixed.css', array(), '1.0.1');

    // Scripts - using fixed JS
    wp_enqueue_script('workcity-chat-js', plugin_dir_url(__FILE__) . 'assets/js/chat-fixed.js', array('jquery'), '1.0.1', true);
    
    // Product chat script if exists
    if (file_exists(plugin_dir_path(__FILE__) . 'assets/js/product-chat.js')) {
        wp_enqueue_script('workcity-product-chat', plugin_dir_url(__FILE__) . 'assets/js/product-chat.js', array('jquery'), '1.0.1', true);
    }

    // Localize script with necessary data
    wp_localize_script('workcity-chat-js', 'workcityChatData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('workcity_chat_nonce'),
        'current_user_id' => get_current_user_id(),
        'current_user_name' => wp_get_current_user()->display_name,
        'current_user_role' => workcity_get_user_role(get_current_user_id())
    ));
}
add_action('wp_enqueue_scripts', 'workcity_chat_enqueue_assets');

/**
 * Check database tables on admin init
 */
function workcity_check_chat_tables() {
    if (is_admin() && current_user_can('manage_options')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'workcity_messages';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Try to create table
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            workcity_chat_install();
            
            // Add admin notice if still not created
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                add_action('admin_notices', 'workcity_chat_table_notice');
            }
        }
    }
}
add_action('admin_init', 'workcity_check_chat_tables');

/**
 * Admin notice for missing tables
 */
function workcity_chat_table_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Workcity Chat: Database tables could not be created. Please check your database permissions.', 'workcity-chat'); ?></p>
    </div>
    <?php
}
