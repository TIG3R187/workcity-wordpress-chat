<?php
/**
 * REST API Endpoints for Workcity Chat - Refactored
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes.
 */
add_action('rest_api_init', function () {
    // Route to get messages for a session
    register_rest_route('workcity/v1', '/messages/(?P<session_id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'workcity_rest_get_messages',
        'permission_callback' => 'workcity_rest_permission_check',
        'args'                => [
            'session_id' => [
                'validate_callback' => function($param) { return is_numeric($param); }
            ],
        ],
    ]);

    // Route to send a message
    register_rest_route('workcity/v1', '/messages', [
        'methods'             => 'POST',
        'callback'            => 'workcity_rest_send_message',
        'permission_callback' => 'workcity_rest_permission_check',
    ]);
});

/**
 * Permission check for the REST API endpoints.
 * Ensures the user is logged in.
 *
 * @return bool|WP_Error
 */
function workcity_rest_permission_check() {
    if (!is_user_logged_in()) {
        return new WP_Error('rest_forbidden', 'You must be logged in to use this endpoint.', ['status' => 401]);
    }
    return true;
}

/**
 * REST API callback to fetch messages for a session.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function workcity_rest_get_messages(WP_REST_Request $request) {
    $session_id = intval($request['session_id']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'workcity_messages';

    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT m.*, u.display_name as sender_name FROM {$table_name} m
         LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
         WHERE m.session_id = %d
         ORDER BY m.created_at ASC",
        $session_id
    ));

    // Add sender role to each message object
    if ($messages) {
        foreach ($messages as $key => $message) {
            $messages[$key]->sender_role = workcity_get_user_role($message->sender_id);
        }
    }

    return rest_ensure_response($messages);
}

/**
 * REST API callback to send a message.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function workcity_rest_send_message(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $session_id = isset($params['session_id']) ? intval($params['session_id']) : 0;
    $message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';

    if (empty($session_id) || empty($message)) {
        return new WP_Error('invalid_params', 'Invalid chat session or empty message.', ['status' => 400]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'workcity_messages';
    $sender_id = get_current_user_id();

    $data = [
        'session_id' => $session_id,
        'sender_id'  => $sender_id,
        'message'    => $message,
        'created_at' => current_time('mysql'),
        'is_read'    => 0
    ];
    $format = ['%d', '%d', '%s', '%s', '%d'];

    $result = $wpdb->insert($table_name, $data, $format);

    if ($result) {
        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response(['success' => true, 'message' => $data]);
    } else {
        return new WP_Error('send_error', 'Failed to save message.', ['status' => 500]);
    }
}
