
<?php
/**
 * Installer for Workcity Chat
 * Creates necessary database tables and default settings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Install function - creates database tables
 */
function workcity_chat_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Messages table
    $table_name = $wpdb->prefix . 'workcity_messages';
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id bigint(20) NOT NULL,
        sender_id bigint(20) NOT NULL,
        message longtext NOT NULL,
        file_url varchar(255) DEFAULT '',
        mime_type varchar(100) DEFAULT '',
        is_read tinyint(1) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY session_id (session_id),
        KEY sender_id (sender_id)
    ) $charset_collate;";
    
    // Sessions table
    $sessions_table = $wpdb->prefix . 'workcity_chat_sessions';
    
    $sql_sessions = "CREATE TABLE $sessions_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        creator_id bigint(20) NOT NULL,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY creator_id (creator_id),
        KEY status (status)
    ) $charset_collate;";
    
    // Session participants table
    $participants_table = $wpdb->prefix . 'workcity_chat_participants';
    
    $sql_participants = "CREATE TABLE $participants_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        role varchar(50) DEFAULT 'member',
        joined_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY session_user (session_id,user_id),
        KEY session_id (session_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create/update tables
    dbDelta($sql);
    dbDelta($sql_sessions);
    dbDelta($sql_participants);
    
    // Add version to options
    update_option('workcity_chat_db_version', '1.0');
    
    // Create default chat session if none exists
    $default_session_exists = $wpdb->get_var("SELECT COUNT(*) FROM $sessions_table");
    
    if ($default_session_exists == 0) {
        // Create a default session
        $wpdb->insert(
            $sessions_table,
            array(
                'title' => 'General Chat',
                'creator_id' => 1, // Admin user
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        
        // Get the inserted session ID
        $session_id = $wpdb->insert_id;
        
        // Add admin as participant
        if ($session_id) {
            $wpdb->insert(
                $participants_table,
                array(
                    'session_id' => $session_id,
                    'user_id' => 1,
                    'role' => 'admin',
                    'joined_at' => current_time('mysql')
                )
            );
            
            // Add welcome message
            $wpdb->insert(
                $table_name,
                array(
                    'session_id' => $session_id,
                    'sender_id' => 1,
                    'message' => 'Welcome to the chat! This is a default message.',
                    'is_read' => 0,
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
}

/**
 * Check if database needs updating
 */
function workcity_chat_check_db_version() {
    $current_version = get_option('workcity_chat_db_version', '0');
    
    if (version_compare($current_version, '1.0', '<')) {
        workcity_chat_install();
    }
}
add_action('plugins_loaded', 'workcity_chat_check_db_version');
