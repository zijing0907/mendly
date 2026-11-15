<?php
// Floating chat widget — included as a partial from footer.php on every
// page, so it appears site-wide rather than needing its own URL.
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// The widget needs to know whether the logged-in person is a customer or
// an expert, so chat.js can tell "me" bubbles from "them" bubbles.
$currentRole   = $_SESSION['user_role'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;
?>
<?php if ($currentRole): ?>
  <button id="launcher">💬</button>

  <div id="popup">
    <div class="header">
      <strong>Chat</strong>
      <button id="minimize">▾</button>
    </div>
    <div class="body">
      <div class="list">
        <input id="search" placeholder="Search name">
        <div class="list-items" id="listItems"></div>
      </div>
      <div class="conversation">
        <div class="conv-header" id="convHeader"></div>
        <div class="messages" id="convMessages"></div>
        <div class="staged-attachments" id="stagedAttachments"></div>
        <div class="composer">
          <button id="attachBtn" class="icon-btn attach-btn" aria-label="Attach media" type="button">+</button>
          <input type="file" id="attachInput" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4" multiple hidden>
          <textarea id="composerInput" rows="1" placeholder="Type message here"></textarea>
          <button id="sendBtn" class="icon-btn send-btn" aria-label="Send" type="button">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path d="M2 21l21-9L2 3v7l15 2-15 2z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Tells chat.js which side of each message ("customer"/"expert") is "me".
    const CURRENT_ROLE = <?php echo json_encode($currentRole); ?>;
    const CURRENT_USER_ID = <?php echo json_encode($currentUserId); ?>;
  </script>
  <script src="assets/js/chat.js"></script>
<?php endif; ?>