<?php
/**
 * REST API Endpoints for Workcity Chat - Integrated Version
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
    
    // Additional routes for chat sessions
    register_rest_route('workcity/v1', '/sessions', [
        'methods'             => 'GET',
        'callback'            => 'workcity_rest_get_sessions',
        'permission_callback' => 'workcity_rest_permission_check',
    ]);
    
    register_rest_route('workcity/v1', '/sessions/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'workcity_rest_get_session',
        'permission_callback' => 'workcity_rest_permission_check',
        'args'                => [
            'id' => [
                'validate_callback' => function($param) { return is_numeric($param); }
            ],
        ],
    ]);
    
    register_rest_route('workcity/v1', '/sessions/(?P<id>\d+)/messages', [
        'methods'             => 'GET',
        'callback'            => 'workcity_rest_get_session_messages',
        'permission_callback' => 'workcity_rest_permission_check',
        'args'                => [
            'id' => [
                'validate_callback' => function($param) { return is_numeric($param); }
            ],
        ],
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
        $data['sender_name'] = wp_get_current_user()->display_name;
        $data['sender_role'] = workcity_get_user_role($sender_id);
        
        // Update session updated_at timestamp
        $sessions_table = $wpdb->prefix . 'workcity_chat_sessions';
        $wpdb->update(
            $sessions_table,
            array('updated_at' => current_time('mysql')),
            array('id' => $session_id),
            array('%s'),
            array('%d')
        );
        
        return rest_ensure_response(['success' => true, 'message' => $data]);
    } else {
        return new WP_Error('send_error', 'Failed to save message.', ['status' => 500]);
    }
}

/**
 * Get all chat sessions for the current user
 */
function workcity_rest_get_sessions() {
    global $wpdb;
    $user_id = get_current_user_id();
    
    // Get sessions where user is a participant
    $sessions_table = $wpdb->prefix . 'workcity_chat_sessions';
    $participants_table = $wpdb->prefix . 'workcity_chat_participants';
    
    $sessions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT s.* 
            FROM $sessions_table s
            JOIN $participants_table p ON s.id = p.session_id
            WHERE p.user_id = %d AND s.status = 'active'
            ORDER BY s.updated_at DESC",
            $user_id
        )
    );
    
    if (empty($sessions)) {
        return rest_ensure_response([]);
    }
    
    return rest_ensure_response($sessions);
}

/**
 * Get a specific chat session
 */
function workcity_rest_get_session(WP_REST_Request $request) {
    global $wpdb;
    $session_id = $request['id'];
    $user_id = get_current_user_id();
    
    // Check if user is a participant
    $participants_table = $wpdb->prefix . 'workcity_chat_participants';
    $is_participant = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $participants_table WHERE session_id = %d AND user_id = %d",
            $session_id, $user_id
        )
    );
    
    if (!$is_participant) {
        return new WP_Error('not_authorized', 'You are not authorized to view this session', ['status' => 403]);
    }
    
    // Get session details
    $sessions_table = $wpdb->prefix . 'workcity_chat_sessions';
    $session = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $sessions_table WHERE id = %d",
            $session_id
        )
    );
    
    if (!$session) {
        return new WP_Error('not_found', 'Session not found', ['status' => 404]);
    }
    
    // Get participants
    $participants = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT p.*, u.display_name 
            FROM $participants_table p
            JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE p.session_id = %d",
            $session_id
        )
    );
    
    $session->participants = $participants;
    
    return rest_ensure_response($session);
}

/**
 * Get messages for a specific chat session
 */
function workcity_rest_get_session_messages(WP_REST_Request $request) {
    global $wpdb;
    $session_id = $request['id'];
    $user_id = get_current_user_id();
    
    // Check if user is a participant
    $participants_table = $wpdb->prefix . 'workcity_chat_participants';
    $is_participant = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $participants_table WHERE session_id = %d AND user_id = %d",
            $session_id, $user_id
        )
    );
    
    if (!$is_participant) {
        return new WP_Error('not_authorized', 'You are not authorized to view messages in this session', ['status' => 403]);
    }
    
    // Get messages
    $messages_table = $wpdb->prefix . 'workcity_messages';
    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
            FROM $messages_table m
            JOIN {$wpdb->users} u ON m.sender_id = u.ID
            WHERE m.session_id = %d
            ORDER BY m.created_at ASC",
            $session_id
        )
    );
    
    // Add sender role to each message
    foreach ($messages as $idx => $message) {
        $messages[$idx]->sender_role = workcity_get_user_role($message->sender_id);
    }
    
    // Mark messages as read
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE $messages_table 
            SET is_read = 1 
            WHERE session_id = %d AND sender_id != %d AND is_read = 0",
            $session_id, $user_id
        )
    );
    
    return rest_ensure_response($messages);
}