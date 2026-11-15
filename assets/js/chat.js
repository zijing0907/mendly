let activeId = null;
let conversations = [];

const launcher = document.getElementById("launcher");
const popup = document.getElementById("popup");
const listItems = document.getElementById("listItems");
const convHeader = document.getElementById("convHeader");
const convMessages = document.getElementById("convMessages");
const composerInput = document.getElementById("composerInput");
const search = document.getElementById("search");
const attachBtn = document.getElementById("attachBtn");
const attachInput = document.getElementById("attachInput");
const stagedAttachments = document.getElementById("stagedAttachments");
const sendBtn = document.getElementById("sendBtn");
const composerEl = document.querySelector("#popup .composer");

// Files picked but not yet sent. Each entry: { file, previewUrl }
let pendingFiles = [];

const CHECK_SINGLE = '<svg width="14" height="14" viewBox="0 0 16 15" fill="none"><path d="M1 8l4 4L15 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
const CHECK_DOUBLE = '<svg width="16" height="14" viewBox="0 0 18 14" fill="none"><path d="M1 7l4 4L14 1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 7l4 4L19 1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

function initials(name) {
  return name.split(" ").filter(Boolean).slice(0, 2).map(w => w[0]).join("").toUpperCase();
}

// Everything that comes from the database (names, message text) is escaped
// before it goes anywhere near innerHTML, so a message can't inject HTML or
// script into the other person's page.
function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// MySQL TIMESTAMP columns come back as "YYYY-MM-DD HH:MM:SS" (no timezone
// suffix); swap the space for "T" so the browser parses it as a local time
// instead of guessing.
function toDate(sqlDateTime) {
  return new Date(sqlDateTime.replace(" ", "T"));
}

function formatTime(sqlDateTime) {
  return toDate(sqlDateTime).toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
}

function dateKey(sqlDateTime) {
  return sqlDateTime.slice(0, 10); // "YYYY-MM-DD"
}

function formatDateSep(sqlDateTime) {
  const d = toDate(sqlDateTime);
  const today = new Date();
  const yesterday = new Date();
  yesterday.setDate(today.getDate() - 1);
  const sameDay = (a, b) => a.toDateString() === b.toDateString();

  if (sameDay(d, today)) return "Today";
  if (sameDay(d, yesterday)) return "Yesterday";
  return d.toLocaleDateString([], { month: "long", day: "numeric", year: "numeric" });
}

// Shows/hides the composer (attach button, textarea, send button). Hidden
// whenever there's no active conversation selected yet.
function setComposerVisible(visible) {
  if (!composerEl) return;
  composerEl.classList.toggle("is-hidden", !visible);
}

// Renders a placeholder in the message pane when no conversation is open.
function renderEmptyConversation() {
  convHeader.innerHTML = "";
  convMessages.innerHTML = `<div class="conv-empty">Select a conversation to start chatting</div>`;
  setComposerVisible(false);
}

async function loadConversations() {
  const res = await fetch("api/chat-fetch.php?action=list");
  if (!res.ok) return;
  conversations = await res.json();

  if (!conversations.length) {
    activeId = null;
    renderList();
    renderEmptyConversation();
    return;
  }

  if (!activeId) {
    renderList();
    renderEmptyConversation();
    return;
  }

  renderList();
  loadMessages(activeId);
}

function renderList() {
  const q = search.value.toLowerCase();
  listItems.innerHTML = "";
  conversations
    .filter(c => c.contact_name.toLowerCase().includes(q))
    .forEach(c => {
      const div = document.createElement("div");
      div.className = "contact" + (c.conversation_id === activeId ? " active" : "");
      div.innerHTML = `
        <div class="avatar">${escapeHtml(initials(c.contact_name))}</div>
        <div class="info">
          <div class="name">${escapeHtml(c.contact_name)}</div>
          <div class="preview">${escapeHtml(c.last_message ?? "📎 Attachment")}</div>
        </div>
        ${c.unread_count > 0 ? '<div class="dot"></div>' : ""}
      `;
      div.onclick = () => {
        activeId = c.conversation_id;
        c.unread_count = 0;
        pendingFiles.forEach(entry => URL.revokeObjectURL(entry.previewUrl));
        pendingFiles = [];
        renderStagedAttachments();
        renderList();
        setComposerVisible(true);
        loadMessages(activeId);
      };
      listItems.appendChild(div);
    });
}

function renderConvHeader(convo) {
  convHeader.innerHTML = convo
    ? `<div class="avatar">${escapeHtml(initials(convo.contact_name))}</div>
       <div class="conv-header-name">${escapeHtml(convo.contact_name)}</div>`
    : "";
}

// Keep this in sync with $maxBytes in chat_upload.php.
const MAX_ATTACHMENT_BYTES = 15 * 1024 * 1024; // 15MB

function renderStagedAttachments() {
  stagedAttachments.innerHTML = "";
  pendingFiles.forEach((entry, index) => {
    const item = document.createElement("div");
    item.className = "staged-item";

    const isVideo = entry.file.type.startsWith("video/");
    const mediaEl = document.createElement(isVideo ? "video" : "img");
    mediaEl.src = entry.previewUrl;
    if (isVideo) mediaEl.muted = true;

    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "remove-btn";
    removeBtn.setAttribute("aria-label", "Remove");
    removeBtn.textContent = "×";
    removeBtn.onclick = () => {
      URL.revokeObjectURL(entry.previewUrl);
      pendingFiles.splice(index, 1);
      renderStagedAttachments();
    };

    item.appendChild(mediaEl);
    item.appendChild(removeBtn);
    stagedAttachments.appendChild(item);
  });
}

async function uploadPendingFile(entry) {
  const formData = new FormData();
  formData.append("attachment", entry.file);
  formData.append("conversation_id", activeId);
  const res = await fetch("api/chat-upload.php", { method: "POST", body: formData });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error || `Failed to upload ${entry.file.name}`);
  }
}

function buildMessageRow(m) {
  const isMe = m.sender_type === CURRENT_ROLE;
  const isAttachment = !!m.attachment_path;

  const row = document.createElement("div");
  row.className = "msg-row " + (isMe ? "me" : "them");

  const bubble = document.createElement("div");
  bubble.className = "msg " + (isMe ? "me" : "them") + (isAttachment ? " attachment" : "");

  if (isAttachment) {
    const isVideo = /\.mp4$/i.test(m.attachment_path);
    const mediaEl = document.createElement(isVideo ? "video" : "img");
    mediaEl.src = m.attachment_path;
    if (isVideo) mediaEl.controls = true;
    else mediaEl.alt = "Attachment";
    bubble.appendChild(mediaEl);
    row.appendChild(bubble);

    // Media messages show their timestamp/status below the bubble rather
    // than inline over the text, per .msg-time (as opposed to .msg-time-inline).
    const timeEl = document.createElement("span");
    timeEl.className = "msg-time";
    const isRead = m.is_read == 1;
    const statusHtml = isMe
      ? `<span class="msg-status ${isRead ? "read" : ""}">${isRead ? CHECK_DOUBLE : CHECK_SINGLE}</span>`
      : "";
    timeEl.innerHTML = `${formatTime(m.sent_at)}${statusHtml}`;
    row.appendChild(timeEl);

    return row;
  }

  const textEl = document.createElement("div");
  textEl.className = "msg-text collapsed";
  textEl.textContent = m.message_text;

  const timeEl = document.createElement("span");
  timeEl.className = "msg-time-inline";
  const isRead = m.is_read == 1;
  const statusHtml = isMe
    ? `<span class="msg-status ${isRead ? "read" : ""}">${isRead ? CHECK_DOUBLE : CHECK_SINGLE}</span>`
    : "";
  timeEl.innerHTML = `${formatTime(m.sent_at)}${statusHtml}`;

  textEl.appendChild(timeEl);
  bubble.appendChild(textEl);
  row.appendChild(bubble);

  // Only show "Read more" if the text is actually clamped by .collapsed's
  // max-height. Measured after paint since collapsed elements need layout.
  requestAnimationFrame(() => {
    if (textEl.scrollHeight > textEl.clientHeight + 2) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "read-more-btn";
      btn.textContent = "Read more";
      btn.onclick = () => {
        const stillCollapsed = textEl.classList.toggle("collapsed");
        btn.textContent = stillCollapsed ? "Read more" : "Read less";
      };
      row.appendChild(btn);
    } else {
      textEl.classList.remove("collapsed");
    }
  });

  return row;
}

async function loadMessages(conversationId) {
  const res = await fetch(`api/chat-fetch.php?action=messages&conversation_id=${conversationId}`);
  if (!res.ok) return;
  const messages = await res.json();

  const convo = conversations.find(c => c.conversation_id === conversationId);
  renderConvHeader(convo);
  setComposerVisible(true);
  convMessages.innerHTML = "";

  let lastDateKey = null;
  messages.forEach(m => {
    const dk = dateKey(m.sent_at);
    if (dk !== lastDateKey) {
      const sep = document.createElement("div");
      sep.className = "date-sep";
      sep.textContent = formatDateSep(m.sent_at);
      convMessages.appendChild(sep);
      lastDateKey = dk;
    }
    convMessages.appendChild(buildMessageRow(m));
  });

  convMessages.scrollTop = convMessages.scrollHeight;
}

function autoResizeComposer() {
  const maxHeight = 120;
  composerInput.style.height = "auto";
  const next = Math.min(composerInput.scrollHeight, maxHeight);
  composerInput.style.height = next + "px";
  composerInput.style.overflowY = composerInput.scrollHeight > maxHeight ? "auto" : "hidden";
}

async function sendMessage() {
  const text = composerInput.value.trim();
  if (!text && pendingFiles.length === 0) return;
  if (!activeId) return;

  sendBtn.disabled = true;
  attachBtn.disabled = true;

  try {
    // Attachments go out first, each as its own message, in the order they
    // were staged; the caption (if any) follows as a separate text message.
    for (const entry of pendingFiles) {
      await uploadPendingFile(entry);
    }
    pendingFiles.forEach(entry => URL.revokeObjectURL(entry.previewUrl));
    pendingFiles = [];
    renderStagedAttachments();

    if (text) {
      const res = await fetch("api/chat-send.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ conversation_id: activeId, text })
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error || "Failed to send message");
      }
    }

    composerInput.value = "";
    autoResizeComposer();
    await loadMessages(activeId);
    await loadConversations();
  } catch (err) {
    alert(err.message || "Something went wrong while sending.");
  } finally {
    sendBtn.disabled = false;
    attachBtn.disabled = false;
  }
}

document.getElementById("sendBtn").onclick = sendMessage;
composerInput.addEventListener("input", autoResizeComposer);
composerInput.addEventListener("keydown", e => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});
search.addEventListener("input", renderList);

if (attachBtn && attachInput) {
  attachBtn.onclick = () => attachInput.click();

  // Stage the picked files for preview — nothing uploads until Send is clicked.
  // Each file is checked against the size limit on its own, so one oversized
  // file doesn't block the rest of the batch — it's just skipped with its
  // own message.
  attachInput.addEventListener("change", () => {
    const rejected = [];

    Array.from(attachInput.files).forEach(file => {
      if (file.size > MAX_ATTACHMENT_BYTES) {
        rejected.push(`${file.name} (${(file.size / (1024 * 1024)).toFixed(1)}MB — max 15MB)`);
        return;
      }
      pendingFiles.push({ file, previewUrl: URL.createObjectURL(file) });
    });

    attachInput.value = ""; // reset so picking the same file(s) again still fires "change"
    renderStagedAttachments();

    if (rejected.length) {
      alert("Skipped (too large):\n" + rejected.join("\n"));
    }
  });
}

launcher.onclick = () => {
  popup.classList.add("open");
  launcher.classList.add("hidden");
  loadConversations();
};
document.getElementById("minimize").onclick = () => {
  popup.classList.remove("open");
  launcher.classList.remove("hidden");
};

// Hide the composer immediately on load, before any conversation data
// has come back — avoids a flash of the input box before loadConversations()
// resolves.
setComposerVisible(false);