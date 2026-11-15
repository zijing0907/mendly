document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".request-card").forEach((card) => {
    const select = card.querySelector(".status-select");
    const button = card.querySelector(".confirm-status-btn");
    const badge = card.querySelector(".status-badge");

    if (!select || !button) return; // request already Completed/Cancelled — no controls rendered

    select.addEventListener("change", () => {
      button.disabled = select.value === "";
    });

    button.addEventListener("click", async () => {
      const newStatus = select.value;
      if (!newStatus) return;

      const requestId = card.dataset.requestId;
      button.disabled = true;
      const originalLabel = button.textContent;
      button.textContent = "Updating...";

      try {
        const res = await fetch("update-request-status.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `request_id=${encodeURIComponent(requestId)}&status=${encodeURIComponent(newStatus)}`,
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.success) {
          throw new Error(data.error || "Failed to update status.");
        }

        // Reflect the new status on the card immediately, and reload so
        // the dropdown's available next-steps regenerate server-side.
        badge.textContent = data.status;
        badge.className = "status-badge status-" + data.status.toLowerCase().replace(/\s+/g, "-");

        window.location.reload();
      } catch (err) {
        alert(err.message || "Something went wrong updating the status.");
        button.disabled = false;
        button.textContent = originalLabel;
      }
    });
  });
});