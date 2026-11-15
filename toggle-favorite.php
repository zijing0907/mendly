<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

include("includes/db.php");

$customerId = (int) $_SESSION['user_id'];
$serviceId  = (int) ($_POST['service_id'] ?? 0);

if ($serviceId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_service']);
    exit;
}

// Check whether it's already favorited
$checkStmt = mysqli_prepare(
    $conn,
    "SELECT favorite_id FROM favorites WHERE customer_id = ? AND service_id = ?"
);
mysqli_stmt_bind_param($checkStmt, 'ii', $customerId, $serviceId);
mysqli_stmt_execute($checkStmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
mysqli_stmt_close($checkStmt);

if ($existing) {
    // Already favorited — remove it
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM favorites WHERE favorite_id = ?");
    mysqli_stmt_bind_param($deleteStmt, 'i', $existing['favorite_id']);
    mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    mysqli_close($conn);
    echo json_encode(['favorited' => false]);
} else {
    // Not favorited yet — add it
    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO favorites (customer_id, service_id) VALUES (?, ?)"
    );
    mysqli_stmt_bind_param($insertStmt, 'ii', $customerId, $serviceId);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    mysqli_close($conn);
    echo json_encode(['favorited' => true]);
}
?>
