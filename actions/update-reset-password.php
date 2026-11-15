<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/forgot-password.php'));
    exit;
}

$token              = $_POST['token'] ?? '';
$newPassword        = $_POST['newPassword'] ?? '';
$confirmNewPassword = $_POST['confirmNewPassword'] ?? '';

if ($newPassword === '' || $confirmNewPassword === '') {
    header('Location: ' . url('pages/reset-password.php?token=' . urlencode($token) . '&error=missing_fields'));
    exit;
}

if (strlen($newPassword) < 6) {
    header('Location: ' . url('pages/reset-password.php?token=' . urlencode($token) . '&error=weak_password'));
    exit;
}

if ($newPassword !== $confirmNewPassword) {
    header('Location: ' . url('pages/reset-password.php?token=' . urlencode($token) . '&error=password_mismatch'));
    exit;
}

// Re-validate the token server-side, even though pages/reset-password.php
// already checked it once — never trust a value just because it was
// present in a previous page's form.
$stmt = mysqli_prepare(
    $conn,
    "SELECT reset_id, email, role FROM password_resets
     WHERE token = ? AND used = FALSE AND expires_at > NOW()"
);
mysqli_stmt_bind_param($stmt, 's', $token);
mysqli_stmt_execute($stmt);
$resetRequest = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$resetRequest) {
    mysqli_close($conn);
    header('Location: ' . url('pages/reset-password.php?token=' . urlencode($token) . '&error=invalid_token'));
    exit;
}

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

if ($resetRequest['role'] === 'expert') {
    $updateStmt = mysqli_prepare($conn, "UPDATE experts SET password = ? WHERE email = ?");
} else {
    $updateStmt = mysqli_prepare($conn, "UPDATE customers SET password = ? WHERE email = ?");
}

mysqli_stmt_bind_param($updateStmt, 'ss', $hashedPassword, $resetRequest['email']);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);

    // Mark the token as used so the same link can't be reused
    // Password was reset (maybe because the account was compromised):
    // log out every "Remember me" device
    $idCol    = $resetRequest['role'] === 'expert' ? 'expert_id' : 'customer_id';
    $table    = $resetRequest['role'] === 'expert' ? 'experts' : 'customers';
    $userStmt = mysqli_prepare($conn, "SELECT $idCol AS id FROM $table WHERE email = ?");
    mysqli_stmt_bind_param($userStmt, 's', $resetRequest['email']);
    mysqli_stmt_execute($userStmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));
    if ($user) {
        forgetAllLogins($conn, $resetRequest['role'], (int) $user['id']);
    }

    $usedStmt = mysqli_prepare($conn, "UPDATE password_resets SET used = TRUE WHERE reset_id = ?");
    mysqli_stmt_bind_param($usedStmt, 'i', $resetRequest['reset_id']);
    mysqli_stmt_execute($usedStmt);
    mysqli_stmt_close($usedStmt);

    mysqli_close($conn);

    header('Location: ' . url('pages/login.php?reset=1'));
    exit;
} else {
    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/reset-password.php?token=' . urlencode($token) . '&error=server_error'));
    exit;
}
?>
