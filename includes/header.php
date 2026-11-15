<?php
require_once __DIR__ . '/helpers.php';

$isLoggedIn = isset($_SESSION['user_id']);
$role       = currentRole();
?>

<header class="main-header">
    <div class="header-container">

        <!-- Logo Section -->
        <div id="logo-group">
            <a href="index.php" class="logo-link">
                <img src="assets/images/logo.png" alt="Mendly Logo" class="logo" title="Mendly Logo">
            </a>
        </div>
        <?php
        // __DIR__ makes sure we always load includes/nav.php. A bare
        // include("nav.php") is resolved against the current working
        // directory first, which is how a stray test nav.php in the project
        // root used to get loaded instead.
        include __DIR__ . '/nav.php';
        ?>
    </div>
</header>
<script src="assets/js/header.js"></script>
