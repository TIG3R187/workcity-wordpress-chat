<?php
// includes/cpt-chat-session.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register chat_session CPT
 */
function workcity_register_chat_session_cpt() {
    $labels = array(
        'name'               => 'Chat Sessions',
        'singular_name'      => 'Chat Session',
        'menu_name'          => 'Chat Sessions',
        'name_admin_bar'     => 'Chat Session',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Chat Session',
        'edit_item'          => 'Edit Chat Session',
        'new_item'           => 'New Chat Session',
        'view_item'          => 'View Chat Session',
        'search_items'       => 'Search Chat Sessions',
        'not_found'          => 'No chat sessions found',
        'not_found_in_trash' => 'No chat sessions found in Trash'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'capability_type'    => 'post',
        'supports'           => array( 'title', 'author' ),
        'has_archive'        => false,
        'menu_position'      => 30,
        'menu_icon'          => 'dashicons-format-chat',
    );

    register_post_type( 'chat_session', $args );
}
add_action( 'init', 'workcity_register_chat_session_cpt' );


/**
 * Return an existing chat session for a product+user (or create one).
 *
 * @param int $product_id
 * @param int $user_id
 * @return int $chat_session_id
 */
function workcity_get_or_create_product_chat_session( $product_id, $user_id ) {
    $product_id = intval( $product_id );
    $user_id    = intval( $user_id );

    if ( ! $product_id || ! $user_id ) {
        return 0;
    }

    // Look for an existing chat session for this product where participants include this user.
    $args = array(
        'post_type'      => 'chat_session',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'   => 'product_id',
                'value' => $product_id,
                'compare' => '='
            ),
        ),
    );

    $sessions = get_posts( $args );

    // If sessions exist, try to find one that contains this user in participants
    if ( ! empty( $sessions ) ) {
        foreach ( $sessions as $s ) {
            $participants = get_post_meta( $s->ID, 'participants', true ); // stored as array
            if ( is_array( $participants ) && in_array( $user_id, $participants, true ) ) {
                return (int) $s->ID;
            }
        }
        // If found none with this user, we can reuse first one and append the user to participants
        $s = $sessions[0];
        $participants = get_post_meta( $s->ID, 'participants', true );
        if ( ! is_array( $participants ) ) $participants = array();
        if ( ! in_array( $user_id, $participants, true ) ) {
            $participants[] = $user_id;
            update_post_meta( $s->ID, 'participants', $participants );
        }
        return (int) $s->ID;
    }

    // Create new chat session post
    $product_title = get_the_title( $product_id );
    $postarr = array(
        'post_title'  => sprintf( 'Product Chat: %s (Product #%d)', wp_trim_words( $product_title, 6 ), $product_id ),
        'post_type'   => 'chat_session',
        'post_status' => 'publish',
    );
    $chat_id = wp_insert_post( $postarr );

    if ( $chat_id ) {
        // store product link
        update_post_meta( $chat_id, 'product_id', $product_id );

        // determine seller/author of product (if applicable)
        $seller_id = (int) get_post_field( 'post_author', $product_id );
        $participants = array( $user_id );
        if ( $seller_id && $seller_id !== $user_id ) {
            $participants[] = $seller_id;
        }
        update_post_meta( $chat_id, 'participants', $participants );
    }

    return $chat_id ? (int) $chat_id : 0;
}


/**
 * When a logged in user views a single product page, auto-create/get the session
 * and store it to user meta for quick retrieval by the shortcode.
 */
function workcity_auto_create_product_session_on_view() {
    if ( ! function_exists( 'is_product' ) ) {
        return; // WooCommerce not active
    }

    if ( ! is_product() ) {
        return;
    }

    if ( ! is_user_logged_in() ) {
        return;
    }

    global $post;
    if ( ! $post || $post->post_type !== 'product' ) {
        return;
    }

    $user_id = get_current_user_id();
    $product_id = (int) $post->ID;

    $chat_id = workcity_get_or_create_product_chat_session( $product_id, $user_id );
    if ( $chat_id ) {
        update_user_meta( $user_id, 'current_chat_session', $chat_id );
    }
}
add_action( 'template_redirect', 'workcity_auto_create_product_session_on_view' );
