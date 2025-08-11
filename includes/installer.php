<?php
// includes/installer.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create (or update) the custom chat messages table on plugin activation.
 */
function workcity_chat_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'workcity_messages';
    $charset_collate = $wpdb->get_charset_collate();

    // Note: include file_url and mime_type to support uploads.
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id bigint(20) UNSIGNED NOT NULL,
        sender_id bigint(20) UNSIGNED NOT NULL,
        message text NOT NULL,
        file_url varchar(255) DEFAULT NULL,
        mime_type varchar(100) DEFAULT NULL,
        is_read tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY session_id (session_id),
        KEY sender_id (sender_id)
    ) {$charset_collate};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
