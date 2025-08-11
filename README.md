# Workcity WordPress Chat

A robust, scalable, and user-friendly chat solution for WordPress and WooCommerce. This plugin provides a real-time chat system allowing authenticated users (buyers, merchants, designers, agents) to communicate directly on the platform with product-based context.

---

## Features

This plugin meets all the required features and includes several bonus features for an enhanced user experience.

- **Custom Post Type for Chat Sessions:** Organizes chats efficiently in the WordPress admin area.
- **WooCommerce Integration:** A "Chat with seller" button appears on product pages, launching a chat window pre-configured for that specific product.
- **AJAX-Based Real-Time Messaging:** Messages are sent and received without page reloads using AJAX polling.
- **Role-Based User Experience:** Chat bubbles are styled with unique colors and alignment based on the user's role (e.g., Administrator, Customer), providing a clear and intuitive interface.
- **Read/Unread Status:** New messages are visually highlighted to the user upon receipt.
- **Typing Indicators:** See when another user is actively typing a message.
- **Shortcode Support:**
    - `[workcity_chat session_id="..."]`: Embed a chat window for a specific session on any page.
    - `[workcity_product_chat]`: Place on a WooCommerce product page template to generate the chat button and modal.
- **REST API Endpoints:** Provides a foundation for integration with other systems (`/wp-json/workcity/v1/messages/...`).

---

## Technologies Used

- **Backend:** PHP, WordPress Plugin API, AJAX, REST API, Custom Database Tables
- **Frontend:** JavaScript (jQuery), HTML5, CSS3
- **Platform:** localWordPress & WooCommerce

---

## Setup Instructions

1.  **Installation:**
    - Download the plugin as a `.zip` file.
    - In your WordPress admin dashboard, navigate to `Plugins` > `Add New` > `Upload Plugin`.
    - Choose the downloaded zip file and click `Install Now`.
2.  **Activation:**
    - After installation, click `Activate Plugin`.
    - On activation, the plugin automatically creates a custom database table (`wp_workcity_messages`) to store all chat messages efficiently.
3.  **Usage:**
    - **For WooCommerce:** Add the `[workcity_product_chat]` shortcode to your single product page template. This will automatically display the "Chat with seller" button.
    - **For General Chat:** Use the `[workcity_chat session_id="123"]` shortcode on any page or post, replacing "123" with the ID of a chat session post.

---

## Challenges Encountered & Solutions

The initial version of this plugin faced several architectural challenges that were addressed to ensure scalability and maintainability.

1.  **Challenge: Inefficient & Fragmented Data Storage**
    - **Problem:** Chat messages were being stored in multiple, inconsistent ways (post meta, custom post types for each message), which is highly inefficient and would lead to severe database performance issues at scale.
    - **Solution:** The entire data layer was refactored. A single, indexed custom database table (`wp_workcity_messages`) was created to store all messages. This centralizes the data, allows for efficient querying, and is the best practice for storing relational data in WordPress.

2.  **Challenge: Unmaintainable Backend Logic**
    - **Problem:** The backend code for handling AJAX requests was duplicated and fragmented, with multiple conflicting functions trying to achieve the same goal. This made the code buggy and difficult to debug or extend.
    - **Solution:** All AJAX and REST API handlers were rewritten and unified. There is now a single, clean set of functions responsible for sending and receiving messages, with proper security checks (nonces and permissions) and clear, consistent logic.

3.  **Challenge: Lack of Support for Multiple Chat Instances**
    - **Problem:** The frontend JavaScript was written to handle only one chat box per page, which prevented the WooCommerce product chat modals from functioning correctly.
    - **Solution:** The primary JavaScript file was re-architected to be instance-aware. It can now manage any number of independent chat windows on a single page, ensuring that the product chat modals work flawlessly without interfering with each other.

---

## Potential Future Improvements

- **File Uploads:** Allow users to upload and share images or documents within the chat.
- **Notifications:** Implement email or push notifications to alert users of new messages when they are offline.
- **Dark/Light Mode Toggle:** Add a user-facing switch to toggle between the dark and light themes (the CSS for this already exists).
- **WebSocket Integration:** For true real-time communication, the AJAX polling could be upgraded to a WebSocket-based solution to provide instant message delivery with less server load.

---

## Recent Errors

Fatal error: Cannot redeclare workcity_get_user_role() (previously declared in C:\Users\HP\Local Sites\workcity-chat\app\public\wp-content\plugins\workcity-chat\includes\chat-functions.php:15) in C:\Users\HP\Local Sites\workcity-chat\app\public\wp-content\plugins\workcity-chat\includes\ajax-handlers.php on line 11

---

## Important Notes

*   **OneSignal Push Notifications:** If you have OneSignal Push Notifications enabled, please deactivate it as this plugin handles email push notifications. 
*   **Brevo Integration:** This plugin can be integrated with Brevo (Email, SMS, Web Push, Chat) for enhanced marketing and communication features. Further integration details would be provided based on specific requirements.
*   **YouTube Video:** I will not be able to provide a YouTube video for this project at this time.

---

## 🚧 Challenges Faced So Far 🚧

### 🎨 CSS Role Colors Not Showing

*   **Problem:** CSS classes for roles like `.role-administrator`, `.role-customer`, etc., are defined, but messages are being appended with `admin-msg` / `user-msg` instead of the role-based classes. This mismatch means styles are never applied.

### 🔄 Class Mismatch Between PHP and JS

*   **Problem:** PHP sends a `role_class` (e.g., `role-administrator`), but JavaScript ignores it and uses its own class naming logic. This needs to be unified so styles attach correctly.

### 👻 Live Updates Working, but Styling Missing

*   **Problem:** Fetching messages via AJAX works, and messages display fine, but they have no background colors or text styles.

### 🏗️ HTML Structure Not Matching CSS Expectations

*   **Problem:** CSS expects `.workcity-chat-message` as a base wrapper class, but JavaScript appends plain `<div class="admin-msg">` without that wrapper.

### ❓ Unclear File Placement

*   **Problem:** Initial confusion about where to put CSS and JS files in the WordPress plugin/theme structure. Some CSS was not enqueued or linked properly in WordPress.