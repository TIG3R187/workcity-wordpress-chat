<?php
/**
 * AJAX Handlers for Workcity Chat - Merged & cleaned
 */

if (!defined('ABSPATH')) {
    exit;
}



/**
 * Send chat message (supports optional file upload).
 * Primary action: workcity_send_message
 */
function workcity_chat_send_message() {
    check_ajax_referer('workcity_chat_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to chat.'));
    }

    $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    if (empty($session_id) && empty($_FILES['chat_file'])) {
        wp_send_json_error(array('message' => 'Invalid chat session or empty message/file.'));
    }

    // File upload handling (if present)
    $file_url = '';
    $mime_type = '';

    if (!empty($_FILES['chat_file']) && !empty($_FILES['chat_file']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $overrides = array('test_form' => false);
        $movefile = wp_handle_upload($_FILES['chat_file'], $overrides);

        if (isset($movefile['error'])) {
            wp_send_json_error(array('message' => 'File upload error: ' . $movefile['error']));
        } else {
            $file_url = esc_url_raw($movefile['url']);
            $mime_type = wp_check_filetype($movefile['file'])['type'] ?? '';
            // Optionally generate attachment entry here if you want media-library items.
        }
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'workcity_messages';
    $sender_id = get_current_user_id();

    // If table supports file_url/mime_type, insert those columns; otherwise append marker to message.
    $has_file_columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'file_url'");

    if ($file_url && !empty($has_file_columns)) {
        $data = array(
            'session_id' => $session_id,
            'sender_id' => $sender_id,
            'message' => $message,
            'file_url' => $file_url,
            'mime_type' => $mime_type,
            'created_at' => current_time('mysql'),
            'is_read' => 0
        );
        $format = array('%d','%d','%s','%s','%s','%s','%d');
        $ok = $wpdb->insert($table_name, $data, $format);
    } else {
        if ($file_url) {
            $message_to_store = $message . "\n\n[[FILE::" . $file_url . "]]";
        } else {
            $message_to_store = $message;
        }
        $data = array(
            'session_id' => $session_id,
            'sender_id' => $sender_id,
            'message' => $message_to_store,
            'created_at' => current_time('mysql'),
            'is_read' => 0
        );
        $format = array('%d','%d','%s','%s','%d');
        $ok = $wpdb->insert($table_name, $data, $format);
    }

    if ($ok) {
        $user = wp_get_current_user();
        $new_message = array(
            'id' => $wpdb->insert_id,
            'session_id' => $session_id,
            'sender_id' => $sender_id,
            'sender_name' => $user->display_name,
            'sender_role' => workcity_get_user_role($sender_id),
            'message' => $message,
            'file_url' => $file_url,
            'mime_type' => $mime_type,
            'created_at' => current_time('mysql'),
            'is_read' => 0
        );
        wp_send_json_success(array('message' => $new_message));
    } else {
        wp_send_json_error(array('message' => 'Failed to save message.'));
    }
}
// Primary hook:
add_action('wp_ajax_workcity_send_message', 'workcity_chat_send_message');
// Backwards-compatible aliases used across your codebase:
add_action('wp_ajax_workcity_send_chat_message', 'workcity_chat_send_message');
add_action('wp_ajax_send_chat_message', 'workcity_chat_send_message');
add_action('wp_ajax_workcity_send_chat_message_v2', 'workcity_chat_send_message');
add_action('wp_ajax_nopriv_workcity_send_message', 'workcity_chat_send_message'); // optional - remove if you don't want guests

/**
 * Fetch messages (since last_id) and mark as read (for others' messages).
 * Primary action: workcity_get_messages
 */
function workcity_chat_get_messages() {
    check_ajax_referer('workcity_chat_nonce', 'nonce');

    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        wp_send_json_error(array('message' => 'You must be logged in to view chat.'));
    }

    $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
    $last_id = isset($_POST['last_id']) ? intval($_POST['last_id']) : 0;

    if (empty($session_id)) {
        wp_send_json_error(array('message' => 'Invalid chat session.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'workcity_messages';

    $sql = $wpdb->prepare(
        "SELECT m.* , u.display_name as sender_name
         FROM {$table_name} m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.session_id = %d AND m.id > %d
         ORDER BY m.created_at ASC",
        $session_id,
        $last_id
    );
    $messages = $wpdb->get_results($sql);

    // Mark messages as read (only those sent by others)
    if (!empty($messages)) {
        $message_ids_to_mark_read = array();
        foreach ($messages as $idx => $m) {
            $messages[$idx]->sender_role = workcity_get_user_role($m->sender_id);
            if ((int)$m->sender_id !== (int)$current_user_id) {
                $message_ids_to_mark_read[] = intval($m->id);
            }
        }
        if (!empty($message_ids_to_mark_read)) {
            // safe int cast then implode
            $ids_sql = implode(',', array_map('intval', $message_ids_to_mark_read));
            $wpdb->query("UPDATE {$table_name} SET is_read = 1 WHERE id IN ({$ids_sql})");
        }
    }

    // Return messages; front-end expects res.data.messages or res.data as array
    wp_send_json_success(array('messages' => $messages));
}
add_action('wp_ajax_workcity_get_messages', 'workcity_chat_get_messages');
add_action('wp_ajax_workcity_get_chat_messages', 'workcity_chat_get_messages'); // alias
add_action('wp_ajax_nopriv_workcity_get_messages', 'workcity_chat_get_messages'); // optional - remove if you don't want guests

/**
 * Simple online ping / typing endpoints (optional: add real implementation if needed).
 */
function workcity_online_ping() {
    check_ajax_referer('workcity_chat_nonce', 'nonce');
    // Implement your online presence tracking here (e.g., store transient / user meta)
    wp_send_json_success(array('ok' => true));
}
add_action('wp_ajax_workcity_online_ping', 'workcity_online_ping');
add_action('wp_ajax_nopriv_workcity_online_ping', 'workcity_online_ping');

function workcity_user_typing() {
    check_ajax_referer('workcity_chat_nonce', 'nonce');
    // Implement typing tracking here (transients or option per session)
    wp_send_json_success(array('ok' => true));
}
add_action('wp_ajax_workcity_user_typing', 'workcity_user_typing');
