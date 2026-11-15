<?php
session_start();

include("includes/db.php");

$expertId = requireRole('expert');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add-service.php');
    exit;
}

$serviceId          = (int) ($_POST['serviceId'] ?? 0);   // 0 = new service
$categoryId         = trim($_POST['categoryId'] ?? '');

// Editing: the service must belong to this expert
if ($serviceId > 0) {
    $ownerStmt = mysqli_prepare($conn, "SELECT service_id FROM services WHERE service_id = ? AND expert_id = ?");
    mysqli_stmt_bind_param($ownerStmt, 'ii', $serviceId, $expertId);
    mysqli_stmt_execute($ownerStmt);
    $isOwner = mysqli_fetch_assoc(mysqli_stmt_get_result($ownerStmt)) !== null;
    mysqli_stmt_close($ownerStmt);

    if (!$isOwner) {
        header('Location: my-services.php?msg=not_found');
        exit;
    }
}
$title              = trim($_POST['title'] ?? '');
$description        = trim($_POST['description'] ?? '');
$startingPrice      = trim($_POST['startingPrice'] ?? '');
$estimatedDuration  = trim($_POST['estimatedDuration'] ?? '');

if ($categoryId === '' || $title === '' || $startingPrice === '' || $estimatedDuration === '') {
    header('Location: add-service.php?' . ($serviceId ? 'id=' . $serviceId . '&' : '') . 'error=missing_fields');
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

    header('Location: add-service.php?' . ($serviceId ? 'id=' . $serviceId . '&' : '') . 'error=invalid_category');
    exit;
}

mysqli_stmt_close($checkCategoryStmt);

// Starting price must be a positive number
if (!is_numeric($startingPrice) || (float) $startingPrice <= 0) {
    mysqli_close($conn);

    header('Location: add-service.php?' . ($serviceId ? 'id=' . $serviceId . '&' : '') . 'error=invalid_price');
    exit;
}

$startingPrice = (float) $startingPrice;

if ($serviceId > 0) {
    // Edit: the expert_id condition means an expert can only ever update
    // their own listing, whatever service ID is posted.
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE services
         SET category_id = ?, title = ?, description = ?, starting_price = ?, estimated_duration = ?
         WHERE service_id = ? AND expert_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'issdsii', $categoryId, $title, $description, $startingPrice, $estimatedDuration, $serviceId, $expertId);
    $successUrl = 'my-services.php?msg=updated';
} else {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO services (expert_id, category_id, title, description, starting_price, estimated_duration)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'iissds', $expertId, $categoryId, $title, $description, $startingPrice, $estimatedDuration);
    $successUrl = 'add-service.php?added=1';
}

$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);

header('Location: ' . ($ok ? $successUrl : 'add-service.php?' . ($serviceId ? 'id=' . $serviceId . '&' : '') . 'error=server_error'));
exit;
