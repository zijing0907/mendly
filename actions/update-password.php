<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

// Must be logged in to change a password
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('pages/login.php'));
    exit;
}

include __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/profile.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'customer';

$currentPassword    = $_POST['currentPassword'] ?? '';
$newPassword        = $_POST['newPassword'] ?? '';
$confirmNewPassword = $_POST['confirmNewPassword'] ?? '';

if ($currentPassword === '' || $newPassword === '' || $confirmNewPassword === '') {
    header('Location: ' . url('pages/profile.php?password_error=missing_fields'));
    exit;
}

if (strlen($newPassword) < 6) {
    header('Location: ' . url('pages/profile.php?password_error=weak_password'));
    exit;
}

if ($newPassword !== $confirmNewPassword) {
    header('Location: ' . url('pages/profile.php?password_error=password_mismatch'));
    exit;
}

// Look up the stored password hash for this account, from the correct
// table for this role (customers vs experts each store their own password)
if ($role === 'expert') {
    $stmt = mysqli_prepare($conn, "SELECT password FROM experts WHERE expert_id = ?");
} else {
    $stmt = mysqli_prepare($conn, "SELECT password FROM customers WHERE customer_id = ?");
}

mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$account || !password_verify($currentPassword, $account['password'])) {
    mysqli_close($conn);
    header('Location: ' . url('pages/profile.php?password_error=wrong_password'));
    exit;
}

// Never store plain-text passwords — hash it first
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

if ($role === 'expert') {
    $updateStmt = mysqli_prepare($conn, "UPDATE experts SET password = ? WHERE expert_id = ?");
} else {
    $updateStmt = mysqli_prepare($conn, "UPDATE customers SET password = ? WHERE customer_id = ?");
}

mysqli_stmt_bind_param($updateStmt, 'si', $hashedPassword, $userId);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/profile.php?password_updated=1'));
    exit;
} else {
    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/profile.php?password_error=server_error'));
    exit;
}
?>
