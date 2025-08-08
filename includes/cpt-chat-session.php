<?php
// Register Custom Post Type: Chat Session
function workcity_register_chat_session_cpt() {
    $labels = array(
        'name'               => 'Chat Sessions',
        'singular_name'      => 'Chat Session',
        'menu_name'          => 'Chat Sessions',
        'name_admin_bar'     => 'Chat Session',
        'add_new'            => 'Start New Chat',
        'add_new_item'       => 'Start New Chat Session',
        'edit_item'          => 'Edit Chat',
        'new_item'           => 'New Chat Session',
        'view_item'          => 'View Chat',
        'search_items'       => 'Search Chats',
        'not_found'          => 'No chats found',
        'not_found_in_trash' => 'No chats in trash',
    );

    $args = array(
        'label'               => 'Chat Session',
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-format-chat',
        'supports'            => array('title', 'custom-fields'),
        'capability_type'     => 'post',
        'has_archive'         => false,
        'rewrite'             => false,
    );

    register_post_type('chat_session', $args);
}
add_action('init', 'workcity_register_chat_session_cpt');
