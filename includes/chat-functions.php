<?php
// includes/chat-functions.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the primary role for a user
 * 
 * @param int $user_id
 * @return string
 */
if (!function_exists('workcity_get_user_role')) {
    function workcity_get_user_role($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return 'guest';
        }
        
        $roles = (array) $user->roles;
        if (empty($roles)) {
            return 'guest';
        }
        
        // Map WordPress roles to workcity roles
        $role_mapping = array(
            'administrator' => 'admin',
            'editor' => 'editor',
            'author' => 'author',
            'contributor' => 'contributor',
            'subscriber' => 'customer',
            'customer' => 'customer',
            'shop_manager' => 'merchant',
            'merchant' => 'merchant',
            'designer' => 'designer',
            'agent' => 'agent'
        );
        
        $primary_role = $roles[0];
        return isset($role_mapping[$primary_role]) ? $role_mapping[$primary_role] : $primary_role;
    }
}

function workcity_render_chat_widget() {
    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/chat-widget.php';
    return ob_get_clean();
}
add_shortcode('workcity_chat', 'workcity_render_chat_widget');

function workcity_enqueue_chat_assets() {
    wp_enqueue_script(
        'workcity-chat-js',
        plugin_dir_url(__FILE__) . '../assets/js/chat.js',
        array('jquery'),
        null,
        true
    );

    wp_localize_script('workcity-chat-js', 'workcity_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'workcity_enqueue_chat_assets');

/**
 * Get list of users for private chat
 */
function workcity_get_private_chat_users() {
    $args = [
        'role__in' => ['customer', 'merchant', 'designer', 'agent'],
        'exclude'  => [get_current_user_id()],
    ];
    return get_users($args);
}

/**
 * Debug function to check if database table exists
 */
function workcity_check_database_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'workcity_messages';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Try to create table
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        require_once plugin_dir_path(__FILE__) . 'installer.php';
        workcity_chat_install();
        
        // Check again
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }
    
    // Check table structure
    if ($table_exists) {
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        $expected_columns = array('id', 'session_id', 'sender_id', 'message', 'file_url', 'mime_type', 'is_read', 'created_at');
        $actual_columns = array();
        
        foreach ($columns as $column) {
            $actual_columns[] = $column->Field;
        }
        
        return array(
            'exists' => true,
            'columns' => $actual_columns,
            'missing_columns' => array_diff($expected_columns, $actual_columns)
        );
    }
    
    return array('exists' => false);
}
