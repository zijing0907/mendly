<?php
// Menu at the top of every admin page. Set $adminPage before including.
$pendingStmt = mysqli_query($GLOBALS['conn'], "SELECT COUNT(*) AS total FROM experts WHERE approval_status = 'pending'");
$pendingExperts = $pendingStmt ? (int) mysqli_fetch_assoc($pendingStmt)['total'] : 0;
$adminPage = $adminPage ?? '';
?>
<nav class="admin-nav" aria-label="Admin menu">
    <div class="admin-nav-links">
        <strong>Mendly Admin</strong>
        <a href="inquiries.php" class="<?php echo $adminPage === 'inquiries' ? 'active' : ''; ?>">Inquiries</a>
        <a href="experts.php" class="<?php echo $adminPage === 'experts' ? 'active' : ''; ?>">
            Experts to review
            <?php if ($pendingExperts > 0): ?>
                <span class="admin-nav-count"><?php echo $pendingExperts; ?></span>
            <?php endif; ?>
        </a>
    </div>
    <span class="admin-nav-user">
        Signed in as <strong><?php echo e($_SESSION['admin_username'] ?? 'admin'); ?></strong>
        &middot; <a href="logout.php">Log out</a>
    </span>
</nav>
