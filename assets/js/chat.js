
/* assets/js/chat.js
   Single instance-aware chat frontend.
   Requires localized workcityChatData: { ajax_url, nonce, current_user_id }
*/

jQuery(function($){
  if (typeof workcityChatData === 'undefined') {
    console.error('workcityChatData missing. Ensure wp_localize_script / wp_enqueue_script is set.');
    return;
  }

  // escape helper
  function escHtml(s){ if(s===undefined||s===null) return ''; return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

  // render helper (safe)
  function appendTo($messages, html){
    $messages.append(html);
    $messages.scrollTop($messages[0].scrollHeight);
  }

  // per-instance (support multiple chat containers on same page)
  $('.workcity-chat-container').each(function(){
    var $container = $(this);
    var sessionId = String($container.data('session-id') || '');
    var currentUserId = parseInt($container.data('current-user-id') || workcityChatData.current_user_id || 0, 10);

    var $messages = $container.find('#chat-messages-' + sessionId);
    var $form     = $container.find('#chat-form-' + sessionId);
    var $input    = $container.find('#chat-input-' + sessionId);
    var $file     = $container.find('#chat-file-' + sessionId);
    var $typing   = $container.find('#chat-typing-indicator-' + sessionId);
    var $sendBtn  = $container.find('#chat-send-btn-' + sessionId);

    // fallbacks
    if (!$messages.length) $messages = $container.find('.chat-messages').first();
    if (!$form.length) $form = $container.find('.chat-form').first();
    if (!$input.length) $input = $container.find('textarea, input[type="text"]').first();
    if (!$sendBtn.length) $sendBtn = $container.find('.chat-send-btn').first();

    var lastId = 0;
    var fetching = false;

    function renderMessage(m, opts){
      opts = opts || {};
      if(!m) return;
      // dedupe by id
      if(m.id && $messages.find('[data-message-id="'+m.id+'"]').length) return;

      var role = (m.sender_role || m.role || 'user').toString();
      var roleClass = 'role-' + role.toLowerCase().replace(/[^a-z0-9\-]/g,'-');
      var sender = escHtml(m.sender_name || m.sender || 'User');
      var isMe = parseInt(m.sender_id || 0, 10) === currentUserId;
      var sideClass = isMe ? 'my-message' : 'their-message';
      var time = escHtml(m.created_at || m.time || (new Date()).toLocaleTimeString());

      var fileHtml = '';
      if (m.file_url) {
        var url = escHtml(m.file_url);
        if (/\.(png|jpe?g|gif|webp|svg)$/i.test(url) || (m.mime_type && m.mime_type.indexOf('image/') === 0)) {
          fileHtml = '<div class="chat-file"><img src="'+url+'" style="max-width:240px;max-height:240px;border-radius:6px;" /></div>';
        } else {
          fileHtml = '<div class="chat-file"><a href="'+url+'" target="_blank" rel="noopener">Download file</a></div>';
        }
      } else if (m.message && /\[\[FILE::(https?:\/\/[^\]]+)\]\]/i.test(m.message)) {
        var match = m.message.match(/\[\[FILE::(https?:\/\/[^\]]+)\]\]/i);
        var url = match && match[1];
        m.message = (m.message || '').replace(match[0], '').trim();
        if (url) {
          if (/\.(png|jpe?g|gif|webp|svg)$/i.test(url)) {
            fileHtml = '<div class="chat-file"><img src="'+escHtml(url)+'" style="max-width:240px;max-height:240px;border-radius:6px;" /></div>';
          } else {
            fileHtml = '<div class="chat-file"><a href="'+escHtml(url)+'" target="_blank" rel="noopener">Download file</a></div>';
          }
        }
      }

      var html = '<div class="workcity-chat-message '+roleClass+' '+sideClass+'" data-message-id="'+(m.id||'')+'">'
               +  '<div class="meta"><strong>['+escHtml(role)+'] '+sender+'</strong> <span class="chat-time">'+time+'</span></div>'
               +  '<div class="text">'+escHtml(m.message || '')+'</div>'
               +  fileHtml
               +  (typeof m.is_read !== 'undefined' ? (m.is_read ? '<span class="chat-checkmark chat-checkmark-read">\u2714\u2714</span>' : '<span class="chat-checkmark chat-checkmark-unread">\u2714</span>') : '')
               +  '</div>';
      appendTo($messages, html);
      if (!opts.noScroll) $messages.scrollTop($messages[0].scrollHeight);
    }

    // fetch new messages since lastId
    function fetchMessages() {
      if (fetching) return;
      fetching = true;
      $.post(workcityChatData.ajax_url, {
        action: 'workcity_get_messages',
        nonce: workcityChatData.nonce,
        session_id: sessionId,
        last_id: lastId
      }, function(res){
        fetching = false;
        if (!res) return;
        if (res.success) {
          // server returns { messages: [...] } or array directly
          var arr = Array.isArray(res.data) ? res.data : (res.data && res.data.messages ? res.data.messages : []);
          arr.forEach(function(m){
            renderMessage(m);
            if (m.id) lastId = Math.max(lastId, parseInt(m.id,10));
          });
        } else {
          // silent fail on polling; you can log for dev:
          // console.log('fetchMessages error', res);
        }
      }, 'json').fail(function(xhr, status, err){
        fetching = false;
        // network failing sometimes is ok; log only for debugging
        // console.error('fetchMessages failed', status, err);
      });
    }

    // send message (supports file)
    function sendMessage() {
      var text = ($input.val()||'').trim();
      var fileEl = ($file && $file.length ? $file[0] : null);
      var hasFile = fileEl && fileEl.files && fileEl.files.length > 0;
      if (!text && !hasFile) return;

      var fd = new FormData();
      fd.append('action', 'workcity_send_message');
      fd.append('nonce', workcityChatData.nonce);
      fd.append('session_id', sessionId);
      if (text) fd.append('message', text);
      if (hasFile) fd.append('chat_file', fileEl.files[0]);

      // optimistic UI: show the message right away as "sending"
      var tmpMessage = {
        id: 'tmp-' + Date.now(),
        sender_id: currentUserId,
        sender_name: (workcityChatData.current_user_name || 'You'),
        sender_role: (workcityChatData.current_user_role || 'you'),
        message: text,
        created_at: (new Date()).toLocaleTimeString(),
        is_read: 0
      };
      renderMessage(tmpMessage, { noScroll: false });

      $.ajax({
        url: workcityChatData.ajax_url,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 15000
      }).done(function(res){
        if (res && res.success) {
          // ideally server returns new message object in res.data.message or res.data
          var m = res.data && (res.data.message || res.data);
          if (m) {
            // remove tmp message if present
            $messages.find('[data-message-id="'+tmpMessage.id+'"]').remove();
            renderMessage(m);
            if (m.id) lastId = Math.max(lastId, parseInt(m.id,10));
          } else {
            // fallback: trigger fetch to sync
            fetchMessages();
          }
        } else {
          alert(res && res.data && res.data.message ? res.data.message : 'Send failed');
          // remove tmp message
          $messages.find('[data-message-id="'+tmpMessage.id+'"]').remove();
        }
      }).fail(function(xhr, status, err){
        alert('Network error when sending message');
        // remove tmp message
        $messages.find('[data-message-id="'+tmpMessage.id+'"]').remove();
        console.error('send message failed', status, err, xhr && xhr.responseText);
      }).always(function(){
        $input.val('');
        if (hasFile) { fileEl.value = ''; }
      });
    }

    // Form submission handler
    $form.off('submit.chat').on('submit.chat', function(e){
      e.preventDefault();
      sendMessage();
    });

    // Send button click handler
    if ($sendBtn.length) {
      $sendBtn.off('click.chat').on('click.chat', function(e){
        e.preventDefault();
        sendMessage();
      });
    }

    // Enter key handler (with shift+enter support for newlines)
    $input.on('keypress', function(e){
      if (e.which === 13 && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    // typing ping (optional)
    var typingTimer = null;
    $input.on('input', function(){
      $.post(workcityChatData.ajax_url, { 
        action: 'workcity_user_typing', 
        nonce: workcityChatData.nonce, 
        session_id: sessionId 
      });
      clearTimeout(typingTimer);
      typingTimer = setTimeout(function(){}, 1500);
    });

    // initial load + poll every 2s
    fetchMessages();
    setInterval(fetchMessages, 2000);
  });

  // keep alive ping (optional)
  setInterval(function(){ 
    $.post(workcityChatData.ajax_url, { 
      action: 'workcity_online_ping', 
      nonce: workcityChatData.nonce 
    }); 
  }, 15000);
});

/* Global dark-mode toggle appended outside jQuery ready so it exists even if no container */
(function() {
  if (document.getElementById('chat-dark-toggle')) return;
  var btn = document.createElement('button');
  btn.id = 'chat-dark-toggle';
  btn.setAttribute('aria-label','Toggle chat dark mode');
  btn.style.position = 'fixed';
  btn.style.top = '40px';
  btn.style.left = '50%';
  btn.style.transform = 'translateX(-50%)';
  btn.style.zIndex = '99999';
  btn.style.padding = '8px 12px';
  btn.style.borderRadius = '18px';
  btn.style.border = 'none';
  btn.style.cursor = 'pointer';
  btn.style.boxShadow = '0 2px 8px rgba(0,0,0,0.12)';
  document.body.appendChild(btn);

  function setMode(mode){
    if(mode === 'dark'){
      document.documentElement.classList.add('chat-dark-mode');
      btn.textContent = '\u2600\ufe0f Light Mode';
      btn.style.background = '#111';
      btn.style.color = '#fff';
    } else {
      document.documentElement.classList.remove('chat-dark-mode');
      btn.textContent = '\ud83c\udf19 Dark Mode';
      btn.style.background = '#fff';
      btn.style.color = '#222';
    }
    try { localStorage.setItem('workcityChatMode', mode); } catch(e){}
  }

  var saved = 'light';
  try { saved = localStorage.getItem('workcityChatMode') || 'light'; } catch(e){}
  setMode(saved);

  btn.addEventListener('click', function(){
    var cur = document.documentElement.classList.contains('chat-dark-mode') ? 'dark' : 'light';
    setMode(cur === 'dark' ? 'light' : 'dark');
  });
})();
