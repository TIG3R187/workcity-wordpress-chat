
<?php
/**
 * Chat Widget Template
 * 
 * This template displays the chat interface.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current user info
$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

// Generate a unique session ID if not provided
$session_id = isset($session_id) ? $session_id : (isset($_GET['session']) ? intval($_GET['session']) : 1);
?>

<div class="workcity-chat-container" data-session-id="<?php echo esc_attr($session_id); ?>" data-current-user-id="<?php echo esc_attr($current_user_id); ?>">
    <div class="chat-header">
        <h3 class="chat-title"><?php echo esc_html(isset($chat_title) ? $chat_title : 'Chat'); ?></h3>
    </div>
    
    <div id="chat-messages-<?php echo esc_attr($session_id); ?>" class="chat-messages">
        <!-- Messages will be loaded here -->
    </div>
    
    <div id="chat-typing-indicator-<?php echo esc_attr($session_id); ?>" class="chat-typing-indicator" style="display:none;">
        <small>Someone is typing...</small>
    </div>
    
    <form id="chat-form-<?php echo esc_attr($session_id); ?>" class="chat-form">
        <div class="chat-controls">
            <textarea id="chat-input-<?php echo esc_attr($session_id); ?>" class="chat-input" placeholder="Type your message..." rows="2"></textarea>
            
            <div class="chat-file-wrap">
                <label class="chat-file-label">
                    <span>\ud83d\udcce</span>
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
