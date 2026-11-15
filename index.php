<?php
session_start();
$currentPage = 'home';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mendly</title>
    <link rel="stylesheet" href="assets/css/global.css">

</head>

<body>
    <?php include "includes/header.php"; ?>
    <?php include "includes/main.php"; ?>
    <?php include "includes/footer.php"; ?>
    <script src="assets/js/main.js"></script>
</body>

</html>