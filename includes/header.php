<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$role       = $_SESSION['role'] ?? 'guest';
$fullName   = $_SESSION['full_name'] ?? 'User';
?>

<header class="main-header">
    <div class="header-container">

        <!-- Logo Section -->
        <div id="logo-group">
            <a href="index.php" class="logo-link">
                <img src="assets/images/logo.png" alt="Mendly Logo" class="logo" title="Mendly Logo">
            </a>
        </div>
        <?php include("nav.php"); ?>
    </div>
</header>
<script src="assets/js/header.js"></script>