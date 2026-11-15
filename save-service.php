<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'expert') {
    header('Location: index.php');
    exit;
}

include("includes/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add-service.php');
    exit;
}

$expertId          = (int) $_SESSION['user_id'];
$categoryId         = trim($_POST['categoryId'] ?? '');
$title              = trim($_POST['title'] ?? '');
$description        = trim($_POST['description'] ?? '');
$startingPrice      = trim($_POST['startingPrice'] ?? '');
$estimatedDuration  = trim($_POST['estimatedDuration'] ?? '');

if ($categoryId === '' || $title === '' || $startingPrice === '' || $estimatedDuration === '') {
    header('Location: add-service.php?error=missing_fields');
    exit;
}

// Confirm the submitted category actually exists, rather than trusting
// whatever numeric value was posted
$categoryId = (int) $categoryId;

$checkCategoryStmt = mysqli_prepare($conn, "SELECT category_id FROM categories WHERE category_id = ?");
mysqli_stmt_bind_param($checkCategoryStmt, 'i', $categoryId);
mysqli_stmt_execute($checkCategoryStmt);
mysqli_stmt_store_result($checkCategoryStmt);

if (mysqli_stmt_num_rows($checkCategoryStmt) === 0) {
    mysqli_stmt_close($checkCategoryStmt);
    mysqli_close($conn);

    header('Location: add-service.php?error=invalid_category');
    exit;
}

mysqli_stmt_close($checkCategoryStmt);

// Starting price must be a positive number
if (!is_numeric($startingPrice) || (float) $startingPrice <= 0) {
    mysqli_close($conn);

    header('Location: add-service.php?error=invalid_price');
    exit;
}

$startingPrice = (float) $startingPrice;

$insertServiceStmt = mysqli_prepare(
    $conn,
    "INSERT INTO services (expert_id, category_id, title, description, starting_price, estimated_duration)
     VALUES (?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $insertServiceStmt,
    'iissds',
    $expertId,
    $categoryId,
    $title,
    $description,
    $startingPrice,
    $estimatedDuration
);

if (mysqli_stmt_execute($insertServiceStmt)) {
    mysqli_stmt_close($insertServiceStmt);
    mysqli_close($conn);

    header('Location: add-service.php?added=1');
    exit;
} else {
    mysqli_stmt_close($insertServiceStmt);
    mysqli_close($conn);

    header('Location: add-service.php?error=server_error');
    exit;
}
?>
