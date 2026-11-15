/* ==========================================================================
   HEADER & NAVIGATION INTERACTIVE LOGIC (header.js)
   全站通用：包含 Mobile Drawer 滑出/关闭与 Desktop Profile Dropdown
   ========================================================================== */

document.addEventListener("DOMContentLoaded", () => {
  // ----------------------------------------------------------------------
  // 1. MOBILE DRAWER MENU INTERACTION (移动端/平板 抽屉菜单逻辑)
  // ----------------------------------------------------------------------
  const burgerMenu = document.getElementById("burger-menu");
  const mobileDrawer = document.getElementById("mobileDrawer");
  const drawerOverlay = document.getElementById("drawerOverlay");
  const closeDrawerBtn = document.getElementById("closeDrawerBtn");

  // 切换 Drawer 抽屉与遮罩层的显隐状态
  function toggleDrawer() {
    if (mobileDrawer && drawerOverlay) {
      mobileDrawer.classList.toggle("active");
      drawerOverlay.classList.toggle("active");
    }
  }

  // 绑定点击事件：点击 Burger 按钮打开/关闭 Drawer
  if (burgerMenu) {
    burgerMenu.addEventListener("click", (e) => {
      e.stopPropagation();
      toggleDrawer();
    });
  }

  // 绑定点击事件：点击 Drawer 内部的 'X' 关闭按钮
  if (closeDrawerBtn) {
    closeDrawerBtn.addEventListener("click", toggleDrawer);
  }

  // 绑定点击事件：点击黑色遮罩层关闭 Drawer
  if (drawerOverlay) {
    drawerOverlay.addEventListener("click", toggleDrawer);
  }

  // ----------------------------------------------------------------------
  // 2. DESKTOP PROFILE DROPDOWN LOGIC (桌面端 用户头像下拉菜单)
  // ----------------------------------------------------------------------
  const avatarBtn = document.getElementById("avatarBtn");
  const profileDropdown = document.getElementById("profileDropdown");

  if (avatarBtn && profileDropdown) {
    // 点击头像展开/收起下拉菜单
    avatarBtn.addEventListener("click", (e) => {
      e.stopPropagation(); // 阻止冒泡，防止触发外层 window 点击事件
      profileDropdown.classList.toggle("show");
    });
  }

  // ----------------------------------------------------------------------
  // 3. GLOBAL CLICK LISTENER (点击空白处自动收起 Dropdown)
  // ----------------------------------------------------------------------
  window.addEventListener("click", (e) => {
    // 如果点击的区域不在头像按钮或下拉菜单内部，则自动收起下拉菜单
    if (profileDropdown && profileDropdown.classList.contains("show")) {
      if (!profileDropdown.contains(e.target) && e.target !== avatarBtn) {
        profileDropdown.classList.remove("show");
      }
    }
  });
});
