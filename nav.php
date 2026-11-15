<?php
// ==========================================
// 🧪 临时测试开关：手动修改这个值来测试不同状态
// ==========================================
$isTestLoggedIn = true; // 设为 true 模拟【已登录】，设为 false 模拟【未登录】
$isTestExpert   = false; // 设为 true 模拟以专家身份登录，false 模拟以顾客身份登录

if ($isTestLoggedIn) {
    if ($isTestExpert) {
        $_SESSION['user_id']   = 1; // expert_id 1 (Alex Tan) in mendly-data.sql
        $_SESSION['user_role'] = 'expert';
        $_SESSION['user_name'] = 'Alex Tan';
    } else {
        $_SESSION['user_id']   = 1; // customer_id 1 (Emily Tan) in mendly-data.sql
        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_name'] = 'Emily Tan';
    }
    $_SESSION['user_avatar'] = "assets/images/default-avatar.png";
} else {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_role']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_avatar']);
}
?>

<!-- 1. 桌面端 Primary Navigation (仅在电脑端显示) -->
<nav id="primary-nav">
    <ul class="primary-nav-list">
        <li><a href="index.php" class="<?php echo (isset($currentPage) && $currentPage == 'home') ? 'active' : ''; ?>">Home</a></li>
        <li><a href="browse-service.php" class="<?php echo (isset($currentPage) && $currentPage == 'services') ? 'active' : ''; ?>">Repair Services</a></li>
        <li><a href="find-experts.php" class="<?php echo (isset($currentPage) && $currentPage == 'experts') ? 'active' : ''; ?>">Find Experts</a></li>
        <li><a href="about-us.php" class="<?php echo (isset($currentPage) && $currentPage == 'about') ? 'active' : ''; ?>">About Us</a></li>
        <li><a href="contact.php" class="<?php echo (isset($currentPage) && $currentPage == 'contact') ? 'active' : ''; ?>">Contact</a></li>
    </ul>
</nav>

<!-- 2. 桌面端 Auth / User Avatar (右上角，仅电脑端显示) -->
<div class="header-right">
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- 已登录状态：用户头像 + 下拉菜单 -->
        <div class="profile-dropdown-wrapper">
            <button class="avatar-btn" id="avatarBtn" onclick="toggleProfileDropdown()" aria-label="User Menu">
                <?php if (!empty($_SESSION['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="User Avatar" class="user-avatar">
                <?php else: ?>
                    <span class="user-avatar user-avatar-initial">
                        <?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1))); ?>
                    </span>
                <?php endif; ?>
            </button>

            <div class="profile-dropdown-menu" id="profileDropdown">
                <div class="dropdown-header">
                    <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
                </div>
                <hr>
                <a href="profile.php">Profile</a>
                <a href="my-requests.php">My Requests</a>
                <a href="favorites.php">Favorites</a>
                <a href="settings.php">Settings</a>
                <hr>
                <a href="logout.php" class="logout-link">Log Out</a>
            </div>
        </div>
    <?php else: ?>
        <!-- 未登录状态：Login / Register -->
        <a href="login.php" class="auth-btn">Login / Register</a>
    <?php endif; ?>
</div>

<!-- 3. 移动端/平板 Burger Menu 按钮 (电脑端隐藏) -->
<button id="burger-menu" onclick="toggleMobileDrawer()" aria-label="Toggle Menu">
    <span class="bar"></span>
    <span class="bar"></span>
    <span class="bar"></span>
</button>

<!-- 4. 移动端/平板 Drawer Menu (手机和平板专用抽屉) -->
<!-- 遮罩层 (点击遮罩可以关闭 Drawer) -->
<div class="mobile-drawer-overlay" id="drawerOverlay"></div>

<!-- 抽屉菜单主体 -->
<div class="mobile-drawer" id="mobileDrawer">
    <div class="drawer-header">
        <h3>Menu</h3>
        <button class="close-drawer-btn" id="closeDrawerBtn">&times;</button>
    </div>

    <!-- 主导航链接 -->
    <ul class="drawer-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="browse-service.php">Repair Services</a></li>
        <li><a href="find-experts.php">Find Experts</a></li>
        <li><a href="about-us.php">About Us</a></li>
        <li><a href="contact.php">Contact</a></li>
    </ul>

    <hr class="drawer-divider">

    <!-- 个人账户区域 -->
    <div class="drawer-account-section">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="drawer-section-title">MY ACCOUNT</span>
            <ul class="drawer-links">
                <li><a href="profile.php">Profile</a></li>
                <li><a href="my-requests.php">My Requests</a></li>
                <li><a href="favorites.php">Favorites</a></li>
                <li><a href="settings.php">Account Settings</a></li>
                <li><a href="logout.php" class="btn-logout">Log Out</a></li>
            </ul>
        <?php else: ?>
            <a href="login.php" class="btn-drawer-login">Login / Register</a>
        <?php endif; ?>
    </div>
</div>