🚧 Workcity WordPress Chat
A robust, scalable, and user-friendly chat solution for WordPress & WooCommerce.
Real-time-ish chat using AJAX (polling), instance-aware frontend, WooCommerce product chat, file uploads, role-based styling and read/unread states.

✨ Highlights
✅ Single custom indexed table for messages (wp_workcity_messages) for performance.

✅ Instance-aware front-end: multiple chat windows / modals on the same page.

✅ Unified AJAX handlers with nonce checks and consistent response shape.

✅ File upload support (via AJAX / wp_handle_upload) with image preview.

✅ Read/unread, typing indicators, role-based bubble styling.

✅ Dark / Light theme support — safe page-wide dark-mode CSS (no global invert()).

📦 Features
CPT for chat sessions — manage chat sessions in WP admin.

WooCommerce product chat — [workcity_product_chat] adds “Chat with seller” button + modal.

Shortcodes

[workcity_chat session_id="..."] — embed a chat.

[workcity_product_chat] — product chat button + modal.

AJAX API — workcity_send_message, workcity_get_messages, workcity_online_ping, workcity_user_typing.

REST API (future/optional) — endpoints under /wp-json/workcity/v1/... (scaffolded).

File uploads — images shown inline; other files shown as download links.

Role-aware UI — bubble colors/labels for admin, buyer, merchant, designer, agent, etc.

🛠️ Installation
Upload plugin ZIP to Plugins → Add New → Upload Plugin; or copy the plugin folder to wp-content/plugins/.

Activate plugin.

On activation the plugin runs the installer and creates the wp_workcity_messages table.

⚙️ Quick Usage
Add [workcity_chat session_id="123"] to a page to render a chat window for session 123.

Add [workcity_product_chat] to your product template to show a product-specific chat button.

Ensure users are logged in to use chat (server checks for is_user_logged_in()).

🧩 Current fixes / what I changed
(These are the implemented fixes since the first pass)

🔁 Unified AJAX layer
Consolidated fragmented AJAX handlers into includes/ajax-handlers.php providing single canonical actions (with backward-compatible aliases). All handlers use check_ajax_referer('workcity_chat_nonce','nonce').

🧭 Instance-aware frontend
Rewrote front-end JS so a page can host multiple chat instances (product modals + main chat). Each .workcity-chat-container is independent (session id, current user id).

🗄️ Single messages table
installer.php now creates wp_workcity_messages with proper indexes for session_id and sender_id.

🖼️ File upload support
AJAX workcity_send_message accepts chat_file via FormData; server uses wp_handle_upload and returns file_url/mime_type.

🎨 Dark mode fixes
Replaced global filter: invert() approach with targeted dark-mode CSS (html.chat-dark-mode) to avoid layout breakage. Dark-mode CSS is limited and specific to common theme wrappers and chat elements.

🧽 CSS / layout fixes
Ensured .workcity-chat-container is centered and doesn’t overflow theme columns; .chat-messages is scrollable and bounded (max-height: 60vh). Role color overrides are applied to .workcity-chat-message.role-*.

🧵 Optimistic UI for sending
Frontend inserts a temporary message while AJAX completes, then replaces it with the server message (or re-syncs).

🐞 Current challenges / known issues
These are honest current limitations you should document in the repo and mention in your demo video:

⚠️ Fatal: Cannot redeclare workcity_get_user_role()

Cause: workcity_get_user_role was defined in multiple included files (chat-functions.php and ajax-handlers.php) leading to a fatal PHP redeclare error on plugin load.

Fix (applied): Move workcity_get_user_role() to a single include file (e.g., includes/chat-functions.php) and guard it with if (!function_exists('workcity_get_user_role')) { function workcity_get_user_role(...) { ... } }. For safety, all helper functions use function_exists() guard.

⚠️ AJAX 400 (Bad Request) on admin-ajax.php

Cause: missing or incorrect nonce param, or AJAX body not matching check_ajax_referer expectation.

Fix (applied): Every AJAX call now appends nonce (from workcityChatData.nonce localized in PHP) and uses check_ajax_referer on server. Also ensured FormData includes the action (JS now appends action when using FormData).

⚠️ Dark-mode previously used filter: invert()

Cause: invert filter flips everything causing theme layout & images to flip.

Fix (applied): Replaced with targeted CSS rules under html.chat-dark-mode to only change backgrounds, text color and inputs. Images/videos are left untouched.

⚠️ Role CSS not applied

Cause: JS appended wrong class names (e.g., admin-msg) while CSS expected .role-administrator.

Fix (applied): JS now respects sender_role/role_class returned from server and uses workcityChatData.current_user_role to mark my-message vs their-message. CSS updated to target .workcity-chat-message.role-*.

⚠️ Placement confusion

Clarification: ajax-handlers.php belongs in includes/ (plugin root includes/ajax-handlers.php). The plugin main file (workcitychat.php) require_once includes it. JS/CSS go under assets/js/ and assets/css/ and must be enqueued via wp_enqueue_script / wp_enqueue_style from the main plugin file.

🧑‍💻 Developer notes (where to put what)
PHP

workcitychat.php (main plugin) — register activation hook, enqueue assets and wp_localize_script('workcity-chat-js', 'workcityChatData', [ ... ]).

includes/installer.php — DB table creation (runs on activation).

includes/chat-functions.php — helper functions (e.g., workcity_get_user_role) — guard with function_exists.

includes/ajax-handlers.php — all AJAX handlers (wp_ajax_* hooks). Do not redeclare helpers here.

JS

assets/js/chat.js — instance-aware JS (one file). Enqueue with wp_enqueue_script('workcity-chat-js', ..., array('jquery'), ..., true).

Localize the script with workcityChatData = { ajax_url, nonce, current_user_id, current_user_name, current_user_role }.

CSS

assets/css/chat.css — UI + dark mode + role colors + layout safe-fixes (append the "safer dark-mode" CSS in this README).

Database

Installer creates {$wpdb->prefix}workcity_messages with id, session_id, sender_id, message, file_url (optional), mime_type, is_read, created_at.

🔒 Security & Best Practices
All AJAX endpoints use check_ajax_referer('workcity_chat_nonce', 'nonce').

All user-input is sanitized (sanitize_textarea_field, esc_html when rendering).

File uploads are passed through WordPress wp_handle_upload and MIME type is checked.

Avoid echoing raw DB data without sanitizing.

