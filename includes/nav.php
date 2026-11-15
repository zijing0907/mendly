<?php
// 防御性补丁：如果父文件没定义，这里自动读取，确保绝对不报 undefined 错误
if (!isset($isLoggedIn)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isLoggedIn = isset($_SESSION['user_id']);
    $role       = $_SESSION['role'] ?? 'guest';
    $fullName   = $_SESSION['full_name'] ?? 'User';
}
?>

<!-- 1. 桌面端 Primary Navigation (仅在电脑端显示) -->
<nav id="primary-nav">
    <ul class="primary-nav-list">
        <li><a href="index.php" class="<?php echo (isset($currentPage) && $currentPage == 'home') ? 'active' : ''; ?>">Home</a></li>

        <?php if ($role === 'expert'): ?>
            <!-- 💡 [EXPERT 专属 Nav] 替换了 Repair Services 和 Experts -->
            <li><a href="my-services.php" class="<?php echo (isset($currentPage) && $currentPage == 'my_services') ? 'active' : ''; ?>">My Services</a></li>
            <li><a href="expert-requests.php" class="<?php echo (isset($currentPage) && $currentPage == 'expert_requests') ? 'active' : ''; ?>">Service Requests</a></li>
        <?php else: ?>
            <!-- 💡 [CUSTOMER / GUEST 默认 Nav] -->
            <li><a href="browse-services.php" class="<?php echo (isset($currentPage) && $currentPage == 'services') ? 'active' : ''; ?>">Repair Services</a></li>
            <li><a href="experts.php" class="<?php echo (isset($currentPage) && $currentPage == 'experts') ? 'active' : ''; ?>">Experts</a></li>
        <?php endif; ?>

        <li><a href="about.php" class="<?php echo (isset($currentPage) && $currentPage == 'about') ? 'active' : ''; ?>">About Us</a></li>
        <li><a href="contact.php" class="<?php echo (isset($currentPage) && $currentPage == 'contact') ? 'active' : ''; ?>">Contact</a></li>
    </ul>
</nav>

<!-- 2. 桌面端 Auth / User Avatar (右上角，仅电脑端显示) -->
<div class="header-right">
    <?php if ($isLoggedIn): ?>
        <!-- 已登录状态：用户头像 + 下拉菜单 -->
        <div class="profile-dropdown-wrapper">
            <button class="avatar-btn" id="avatarBtn" onclick="toggleProfileDropdown()" aria-label="User Menu">
                <img src="<?php echo $_SESSION['user_avatar'] ?? 'assets/images/default-avatar.png'; ?>" alt="User Avatar" class="user-avatar">
            </button>

            <div class="profile-dropdown-menu" id="profileDropdown">
                <div class="dropdown-header">
                    <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></strong>
                    <!-- 💡 [ADDED COMMENT] 增加小标签方便标识身份 (Optional) -->
                    <small style="display:block; color:#777; font-size:12px;"><?php echo ucfirst($role); ?></small>
                </div>
                <hr>

                <?php if ($role === 'expert'): ?>
                    <!-- 💡 [EXPERT 专属 Dropdown Menu] 移除 Favorites，指向 Expert 页面 -->
                    <a href="expert-profile.php">Profile</a>
                    <a href="my-services.php">My Services</a>
                    <a href="expert-requests.php">Service Requests</a>
                <?php else: ?>
                    <!-- 💡 [CUSTOMER 默认 Dropdown Menu] -->
                    <a href="profile.php">Profile</a>
                    <a href="my-requests.php">My Requests</a>
                    <a href="wishlist.php">Favorites</a>
                <?php endif; ?>

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

        <?php if ($role === 'expert'): ?>
            <!-- 💡 [EXPERT 移动端 Nav] -->
            <li><a href="my-services.php">My Services</a></li>
            <li><a href="expert-requests.php">Service Requests</a></li>
        <?php else: ?>
            <!-- 💡 [CUSTOMER / GUEST 移动端 Nav] -->
            <li><a href="browse-services.php">Repair Services</a></li>
            <li><a href="experts.php">Experts</a></li>
        <?php endif; ?>

        <li><a href="about.php">About Us</a></li>
        <li><a href="contact.php">Contact</a></li>
    </ul>

    <hr class="drawer-divider">

    <!-- 个人账户区域 -->
    <div class="drawer-account-section">
        <?php if ($isLoggedIn): ?>
            <span class="drawer-section-title">MY ACCOUNT (<?php echo strtoupper($role); ?>)</span>
            <ul class="drawer-links">
                <?php if ($role === 'expert'): ?>
                    <li><a href="expert-profile.php">Profile</a></li>
                <?php else: ?>
                    <li><a href="profile.php">Profile</a></li>
                <?php endif; ?>

                <li><a href="logout.php" class="btn-logout">Log Out</a></li>
            </ul>
        <?php else: ?>
            <a href="login.php" class="btn-drawer-login">Login / Register</a>
        <?php endif; ?>
    </div>
</div>