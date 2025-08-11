<?php
/**
 * Shortcode for Workcity Chat - Refactored
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the main chat application.
 */
function workcity_chat_render_app($session_id) {
    if (empty($session_id)) {
        return '';
    }
    ob_start();
    ?>
    <div id="workcity-chat-app-<?php echo esc_attr($session_id); ?>" class="workcity-chat-container" data-session-id="<?php echo esc_attr($session_id); ?>" data-current-user-id="<?php echo esc_attr(get_current_user_id()); ?>">
        <div class="chat-messages" id="chat-messages-<?php echo esc_attr($session_id); ?>">
            <!-- Messages will be loaded here by JavaScript -->
        </div>
        <div class="chat-typing-indicator" id="chat-typing-indicator-<?php echo esc_attr($session_id); ?>" style="display: none;">
            <p>Someone is typing...</p>
        </div>
        <form class="chat-form" id="chat-form-<?php echo esc_attr($session_id); ?>" enctype="multipart/form-data" data-session-id="<?php echo esc_attr($session_id); ?>">
    <textarea id="chat-input-<?php echo esc_attr($session_id); ?>" placeholder="Type your message..." rows="3"></textarea>

    <!-- file input for uploads -->
    <input type="file" id="chat-file-<?php echo esc_attr($session_id); ?>" name="chat_file" />

    <button type="submit">Send</button>
</form>


    </div>
    <?php
    return ob_get_clean();
}

/**
 * Registers the primary chat shortcode.
 */
function workcity_chat_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to use the chat.</p>';
    }
    $atts = shortcode_atts(['session_id' => ''], $atts, 'workcity_chat');
    $session_id = $atts['session_id'];
    if (empty($session_id)) {
        $session_id = get_user_meta(get_current_user_id(), 'current_chat_session', true);
    }
    if (empty($session_id)) {
        return '<p>No chat session is currently active.</p>';
    }
    return workcity_chat_render_app($session_id);
}
add_shortcode('workcity_chat', 'workcity_chat_shortcode');

/**
 * Shortcode for the WooCommerce "Chat about this product" button and modal.
 */
function workcity_product_chat_button_shortcode() {
    if (!is_user_logged_in() || !function_exists('is_product') || !is_product()) {
        return '';
    }
    global $product;
    if (!is_object($product)) {
        return '';
    }

    // Get or create the session on the server-side
    $session_id = workcity_get_or_create_product_chat_session($product->get_id(), get_current_user_id());

    $button = '<button class="product-chat-button" data-product-id="' . esc_attr($product->get_id()) . '">Chat with seller</button>';

    $modal = '
    <div id="product-chat-modal-' . esc_attr($product->get_id()) . '" class="product-chat-modal">
        <div class="product-chat-window">
            <button class="product-chat-close-btn">&times;</button>
            <div class="product-chat-info">' . esc_html($product->get_name()) . '</div>
            ' . workcity_chat_render_app($session_id) . '
        </div>
    </div>';

    return $button . $modal;
}
add_shortcode('workcity_product_chat', 'workcity_product_chat_button_shortcode');
