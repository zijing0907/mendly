/* ==========================================================================
   HEADER & NAVIGATION INTERACTIVE LOGIC (header.js)
   Site-wide: mobile drawer open/close and the desktop profile dropdown
   ========================================================================== */

// Headers for fetch() calls that change data. Adds this session's CSRF
// token (printed in a <meta> tag by every page); the server rejects POSTs
// without it. See includes/helpers.php.
function csrfHeaders(extra = {}) {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return Object.assign({}, extra, { "X-CSRF-Token": meta ? meta.content : "" });
}

document.addEventListener("DOMContentLoaded", () => {
  // ----------------------------------------------------------------------
  // 1. MOBILE DRAWER MENU INTERACTION (mobile / tablet)
  // ----------------------------------------------------------------------
  const burgerMenu = document.getElementById("burger-menu");
  const mobileDrawer = document.getElementById("mobileDrawer");
  const drawerOverlay = document.getElementById("drawerOverlay");
  const closeDrawerBtn = document.getElementById("closeDrawerBtn");

  // Show/hide the drawer and its overlay together
  function toggleDrawer() {
    if (mobileDrawer && drawerOverlay) {
      mobileDrawer.classList.toggle("active");
      drawerOverlay.classList.toggle("active");
    }
  }

  // Burger button toggles the drawer
  if (burgerMenu) {
    burgerMenu.addEventListener("click", (e) => {
      e.stopPropagation();
      toggleDrawer();
    });
  }

  // The drawer's X button closes it
  if (closeDrawerBtn) {
    closeDrawerBtn.addEventListener("click", toggleDrawer);
  }

  // Clicking the dark overlay closes it too
  if (drawerOverlay) {
    drawerOverlay.addEventListener("click", toggleDrawer);
  }

  // ----------------------------------------------------------------------
  // 2. DESKTOP PROFILE DROPDOWN LOGIC (desktop)
  // ----------------------------------------------------------------------
  const avatarBtn = document.getElementById("avatarBtn");
  const profileDropdown = document.getElementById("profileDropdown");

  if (avatarBtn && profileDropdown) {
    // Clicking the avatar opens/closes the menu
    avatarBtn.addEventListener("click", (e) => {
      e.stopPropagation(); // Don't let the window listener below close it straight away
      profileDropdown.classList.toggle("show");
    });
  }

  // ----------------------------------------------------------------------
  // 3. GLOBAL CLICK LISTENER (click outside closes the dropdown)
  // ----------------------------------------------------------------------
  window.addEventListener("click", (e) => {
    // Close the dropdown when clicking anywhere outside it
    if (profileDropdown && profileDropdown.classList.contains("show")) {
      if (!profileDropdown.contains(e.target) && e.target !== avatarBtn) {
        profileDropdown.classList.remove("show");
      }
    }
  });
});
