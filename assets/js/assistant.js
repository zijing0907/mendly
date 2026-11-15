// "Ask Mendly" help assistant widget (markup in includes/assistant-widget.php).
// Keeps the conversation in sessionStorage, sends it to api/assistant.php,
// and renders replies safely: everything is plain text except links written
// as [Service name](service:ID), which become links to that service.

document.addEventListener("DOMContentLoaded", () => {
  const launcher    = document.getElementById("assistantLauncher");
  const panel       = document.getElementById("assistantPanel");
  const closeBtn    = document.getElementById("assistantClose");
  const list        = document.getElementById("assistantMessages");
  const form        = document.getElementById("assistantForm");
  const input       = document.getElementById("assistantInput");
  const suggestions = document.getElementById("assistantSuggestions");
  if (!launcher || !panel) return;

  const STORAGE_KEY = "mendlyAssistant";
  let history = [];   // [{role: "user"|"model", text: "..."}]
  try {
    history = JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
  } catch (e) {
    history = [];
  }

  function save() {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history.slice(-20)));
  }

  // Build a message bubble. Text is added with createTextNode, so nothing in
  // a reply is ever treated as HTML.
  function addBubble(role, text) {
    const bubble = document.createElement("div");
    bubble.className = "assistant-msg " + (role === "user" ? "assistant-msg-user" : "assistant-msg-bot");

    const linkPattern = /\[([^\]\n]{1,80})\]\(service:(\d{1,7})\)/g;
    let last = 0;
    let match;
    while ((match = linkPattern.exec(text)) !== null) {
      bubble.appendChild(document.createTextNode(text.slice(last, match.index)));
      const link = document.createElement("a");
      link.href = "pages/service-details.php?id=" + match[2];
      link.textContent = match[1];
      bubble.appendChild(link);
      last = match.index + match[0].length;
    }
    bubble.appendChild(document.createTextNode(text.slice(last)));

    list.appendChild(bubble);
    list.scrollTop = list.scrollHeight;
    return bubble;
  }

  // Restore the conversation from earlier pages
  history.forEach((m) => addBubble(m.role, m.text));
  if (history.length) suggestions.hidden = true;

  function setOpen(open) {
    panel.hidden = !open;
    launcher.setAttribute("aria-expanded", String(open));
    if (open) input.focus();
  }
  launcher.addEventListener("click", () => setOpen(panel.hidden));
  closeBtn.addEventListener("click", () => setOpen(false));

  async function ask(question) {
    question = question.trim();
    if (!question) return;

    suggestions.hidden = true;
    history.push({ role: "user", text: question });
    save();
    addBubble("user", question);
    input.value = "";
    input.disabled = true;

    const typing = addBubble("model", "…");
    typing.classList.add("assistant-typing");

    let reply;
    try {
      const res = await fetch("api/assistant.php", {
        method: "POST",
        headers: csrfHeaders({ "Content-Type": "application/json" }),
        body: JSON.stringify({ messages: history.slice(-10) }),
      });
      const data = await res.json().catch(() => ({}));
      reply = data.reply || data.error || "Sorry, something went wrong. Please try again.";
    } catch (e) {
      reply = "I couldn't reach Mendly just now. Please check your connection and try again.";
    }

    typing.remove();
    history.push({ role: "model", text: reply });
    save();
    addBubble("model", reply);
    input.disabled = false;
    input.focus();
  }

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    ask(input.value);
  });
  suggestions.querySelectorAll("button").forEach((btn) => {
    btn.addEventListener("click", () => ask(btn.textContent));
  });
});
