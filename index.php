<?php
require_once __DIR__ . '/includes/helpers.php';   // also starts the session
$currentPage = 'home';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mendly</title>
    <link rel="stylesheet" href="<?php echo asset('assets/css/global.css'); ?>">

</head>

<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <?php include __DIR__ . '/includes/main.php'; ?>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo asset('assets/js/main.js'); ?>"></script>
</body>

</html>