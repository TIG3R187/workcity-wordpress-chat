<?php
if (!is_user_logged_in()) {
    echo '<p>Please log in to use the chat.</p>';
    return;
}

$current_user = wp_get_current_user();
?>
<div id="workcity-chat-box" class="chat-box">
    <div id="chat-messages" class="chat-messages">
        <!-- Messages will load here -->
    </div>
    <form id="chat-form">
        <input type="text" id="chat-input" placeholder="Type your message..." required />
        <button type="submit">Send</button>
    </form>
</div>
