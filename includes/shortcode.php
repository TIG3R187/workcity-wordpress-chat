<?php
/**
 * Shortcode for Workcity Chat - Integrated Version
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
    
    $current_user_id = get_current_user_id();
    
    ob_start();
    ?>
    <div id="workcity-chat-app-<?php echo esc_attr($session_id); ?>" class="workcity-chat-container" data-session-id="<?php echo esc_attr($session_id); ?>" data-current-user-id="<?php echo esc_attr($current_user_id); ?>">
        <div class="chat-header">
            <h3 class="chat-title"><?php echo esc_html(isset($chat_title) ? $chat_title : 'Chat'); ?></h3>
        </div>
        
        <div class="chat-messages" id="chat-messages-<?php echo esc_attr($session_id); ?>">
            <!-- Messages will be loaded here by JavaScript -->
        </div>
        
        <div class="chat-typing-indicator" id="chat-typing-indicator-<?php echo esc_attr($session_id); ?>" style="display: none;">
            <small>Someone is typing...</small>
        </div>
        
        <form class="chat-form" id="chat-form-<?php echo esc_attr($session_id); ?>" enctype="multipart/form-data">
            <div class="chat-controls">
                <textarea id="chat-input-<?php echo esc_attr($session_id); ?>" class="chat-input" placeholder="Type your message..." rows="2"></textarea>
                
                <div class="chat-file-wrap">
                    <label class="chat-file-label">
                        <span>📎</span>
                        <input type="file" id="chat-file-<?php echo esc_attr($session_id); ?>" class="chat-file">
                    </label>
                    <div class="chat-file-preview"></div>
                </div>
                
                <button type="submit" id="chat-send-btn-<?php echo esc_attr($session_id); ?>" class="chat-send-btn">Send</button>
            </div>
        </form>
    </div>

    <script>
    // Preview file before upload
    jQuery(document).ready(function($) {
        $('#chat-file-<?php echo esc_attr($session_id); ?>').on('change', function() {
            var file = this.files[0];
            var preview = $(this).closest('.chat-file-wrap').find('.chat-file-preview');
            
            if (!file) {
                preview.empty();
                return;
            }
            
            if (file.type.match('image.*')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.html('<img src="' + e.target.result + '" alt="Preview">');
                };
                reader.readAsDataURL(file);
            } else {
                preview.html('<span>' + file.name + '</span>');
            }
        });
    });
    </script>
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
    
    $atts = shortcode_atts([
        'session_id' => '',
        'title' => 'Chat',
        'height' => '400px',
    ], $atts, 'workcity_chat');
    
    $session_id = $atts['session_id'];
    $chat_title = sanitize_text_field($atts['title']);
    $height = sanitize_text_field($atts['height']);
    
    if (empty($session_id)) {
        $session_id = get_user_meta(get_current_user_id(), 'current_chat_session', true);
    }
    
    if (empty($session_id)) {
        return '<p>No chat session is currently active.</p>';
    }
    
    $output = workcity_chat_render_app($session_id);
    
    // Add custom height CSS
    $output .= '<style>
        #chat-messages-' . esc_attr($session_id) . ' {
            max-height: ' . esc_attr($height) . ';
        }
    </style>';
    
    return $output;
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

/**
 * Helper function to get or create a product chat session
 */
if (!function_exists('workcity_get_or_create_product_chat_session')) {
    function workcity_get_or_create_product_chat_session($product_id, $user_id) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'workcity_chat_sessions';
        $participants_table = $wpdb->prefix . 'workcity_chat_participants';
        
        // Check if a session already exists for this product and user
        $session_id = $wpdb->get_var($wpdb->prepare(
            "SELECT s.id FROM $sessions_table s 
            JOIN $participants_table p ON s.id = p.session_id
            WHERE s.title LIKE %s AND p.user_id = %d
            LIMIT 1",
            '%Product #' . $product_id . '%',
            $user_id
        ));
        
        if (!$session_id) {
            // Create a new session
            $product = wc_get_product($product_id);
            $title = 'Product #' . $product_id;
            if ($product) {
                $title .= ': ' . $product->get_name();
            }
            
            $wpdb->insert(
                $sessions_table,
                array(
                    'title' => $title,
                    'creator_id' => $user_id,
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s', '%s', '%s')
            );
            
            $session_id = $wpdb->insert_id;
            
            // Add user as participant
            if ($session_id) {
                $wpdb->insert(
                    $participants_table,
                    array(
                        'session_id' => $session_id,
                        'user_id' => $user_id,
                        'role' => 'customer',
                        'joined_at' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%s')
                );
                
                // Add store owner/admin as participant
                $admin_id = get_option('workcity_chat_store_admin', 1);
                if ($admin_id != $user_id) {
                    $wpdb->insert(
                        $participants_table,
                        array(
                            'session_id' => $session_id,
                            'user_id' => $admin_id,
                            'role' => 'merchant',
                            'joined_at' => current_time('mysql')
                        ),
                        array('%d', '%d', '%s', '%s')
                    );
                }
            }
        }
        
        return $session_id;
    }
}