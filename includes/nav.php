<?php
// Site navigation, included by includes/header.php. Links change with the
// visitor's role: guests and customers browse services, experts manage
// their own listings and incoming requests.
require_once __DIR__ . '/helpers.php';

$isLoggedIn = isset($_SESSION['user_id']);
$role       = currentRole();
$navActive  = fn(string $page): string => (isset($currentPage) && $currentPage === $page) ? 'active' : '';

if ($role === 'expert') {
    $mainLinks = [
        ['my-services.php',        'My Services',      'my_services'],
        ['my-requests-expert.php', 'Service Requests', 'expert_requests'],
    ];
    $accountLinks = [
        ['profile.php',            'Profile'],
        ['my-services.php',        'My Services'],
        ['add-service.php',        'Add a Service'],
        ['my-requests-expert.php', 'Service Requests'],
        ['expert-review.php?expert_id=' . (int) $_SESSION['user_id'], 'My Reviews'],
    ];
} else {
    $mainLinks = [
        ['browse-services.php', 'Repair Services', 'services'],
        ['experts.php',         'Experts',         'experts'],
    ];
    $accountLinks = [
        ['profile.php',     'Profile'],
        ['my-requests.php', 'My Requests'],
        ['wishlist.php',    'Favorites'],
        ['review.php',      'Write a Review'],
    ];
}

$displayName = $_SESSION['full_name'] ?? 'User';
?>

<!-- 1. Desktop primary navigation -->
<nav id="primary-nav">
    <ul class="primary-nav-list">
        <li><a href="index.php" class="<?php echo $navActive('home'); ?>">Home</a></li>
        <?php foreach ($mainLinks as [$href, $label, $key]): ?>
            <li><a href="<?php echo $href; ?>" class="<?php echo $navActive($key); ?>"><?php echo $label; ?></a></li>
        <?php endforeach; ?>
        <li><a href="about.php" class="<?php echo $navActive('about'); ?>">About Us</a></li>
        <li><a href="contact.php" class="<?php echo $navActive('contact'); ?>">Contact</a></li>
    </ul>
</nav>

<!-- 2. Desktop account area (top right) -->
<div class="header-right">
    <?php if ($isLoggedIn): ?>
        <div class="profile-dropdown-wrapper">
            <button class="avatar-btn" id="avatarBtn" aria-label="User Menu" aria-haspopup="true">
                <?php if (!empty($_SESSION['avatar'])): ?>
                    <img src="<?php echo e($_SESSION['avatar']); ?>" alt="User Avatar" class="user-avatar">
                <?php else: ?>
                    <span class="user-avatar user-avatar-initial"><?php echo e(strtoupper(substr($displayName, 0, 1))); ?></span>
                <?php endif; ?>
            </button>

            <div class="profile-dropdown-menu" id="profileDropdown">
                <div class="dropdown-header">
                    <strong><?php echo e($displayName); ?></strong>
                    <small class="dropdown-role"><?php echo e(ucfirst($role)); ?></small>
                </div>
                <hr>
                <?php foreach ($accountLinks as [$href, $label]): ?>
                    <a href="<?php echo e($href); ?>"><?php echo $label; ?></a>
                <?php endforeach; ?>
                <hr>
                <a href="logout.php" class="logout-link">Log Out</a>
            </div>
        </div>
    <?php else: ?>
        <a href="login.php" class="auth-btn">Login / Register</a>
    <?php endif; ?>
</div>

<!-- 3. Burger button (tablet / mobile only) -->
<button id="burger-menu" aria-label="Toggle Menu" aria-controls="mobileDrawer">
    <span class="bar"></span>
    <span class="bar"></span>
    <span class="bar"></span>
</button>

<!-- 4. Slide-out drawer (tablet / mobile only). Clicking the overlay closes it. -->
<div class="mobile-drawer-overlay" id="drawerOverlay"></div>

<div class="mobile-drawer" id="mobileDrawer">
    <div class="drawer-header">
        <h3>Menu</h3>
        <button class="close-drawer-btn" id="closeDrawerBtn" aria-label="Close Menu">&times;</button>
    </div>

    <ul class="drawer-links">
        <li><a href="index.php">Home</a></li>
        <?php foreach ($mainLinks as [$href, $label]): ?>
            <li><a href="<?php echo $href; ?>"><?php echo $label; ?></a></li>
        <?php endforeach; ?>
        <li><a href="about.php">About Us</a></li>
        <li><a href="contact.php">Contact</a></li>
    </ul>

    <hr class="drawer-divider">

    <div class="drawer-account-section">
        <?php if ($isLoggedIn): ?>
            <span class="drawer-section-title">MY ACCOUNT (<?php echo e(strtoupper($role)); ?>)</span>
            <ul class="drawer-links">
                <?php foreach ($accountLinks as [$href, $label]): ?>
                    <li><a href="<?php echo e($href); ?>"><?php echo $label; ?></a></li>
                <?php endforeach; ?>
                <li><a href="logout.php" class="btn-logout">Log Out</a></li>
            </ul>
        <?php else: ?>
            <a href="login.php" class="btn-drawer-login">Login / Register</a>
        <?php endif; ?>
    </div>
</div>
