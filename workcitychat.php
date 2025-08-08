<?php
/**
 * Plugin Name: Workcity Chat
 * Description: A real-time chat plugin for buyers, merchants, designers, and agents. WooCommerce-integrated.
 * Version: 1.0
 * Author: Isaac Yakubu
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/cpt-chat-session.php';
