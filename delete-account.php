<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include("includes/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$role     = $_SESSION['role'] ?? 'customer';
$password = $_POST['deletePassword'] ?? '';

if ($password === '') {
    header('Location: profile.php?delete_error=missing_password');
    exit;
}

// Look up the current password hash to confirm it's really them
if ($role === 'expert') {
    $stmt = mysqli_prepare($conn, "SELECT password FROM experts WHERE expert_id = ?");
} else {
    $stmt = mysqli_prepare($conn, "SELECT password FROM customers WHERE customer_id = ?");
}

mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$account || !password_verify($password, $account['password'])) {
    mysqli_close($conn);
    header('Location: profile.php?delete_error=wrong_password');
    exit;
}

// Don't allow deletion if there's repair history tied to this account —
// deleting it silently would lose that record for whoever's on the other
// end of the request (the expert or customer they worked with).
if ($role === 'expert') {
    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM repair_requests rq
         INNER JOIN services s ON rq.service_id = s.service_id
         WHERE s.expert_id = ?"
    );
} else {
    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM repair_requests WHERE customer_id = ?"
    );
}

mysqli_stmt_bind_param($checkStmt, 'i', $userId);
mysqli_stmt_execute($checkStmt);
$requestCount = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))['total'];
mysqli_stmt_close($checkStmt);

if ($requestCount > 0) {
    mysqli_close($conn);
    header('Location: profile.php?delete_error=has_requests');
    exit;
}

// Clear to delete — favorites/services/service_images cascade automatically
// via their foreign keys, so this is the only DELETE statement needed.
if ($role === 'expert') {
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM experts WHERE expert_id = ?");
} else {
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM customers WHERE customer_id = ?");
}

mysqli_stmt_bind_param($deleteStmt, 'i', $userId);

if (mysqli_stmt_execute($deleteStmt)) {
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);

    session_unset();
    session_destroy();

    header('Location: login.php?deleted=1');
    exit;
} else {
    mysqli_stmt_close($deleteStmt);
    mysqli_close($conn);

    header('Location: profile.php?delete_error=server_error');
    exit;
}
?>
