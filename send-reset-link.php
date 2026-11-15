<?php
session_start();
include("includes/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot-password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    header('Location: forgot-password.php?error=missing_email');
    exit;
}

// Check customers first, then experts — same pattern as process-login.php,
// since accounts live in two separate tables.
$stmt = mysqli_prepare($conn, "SELECT customer_id AS id FROM customers WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$role    = 'customer';
mysqli_stmt_close($stmt);

if (!$account) {
    $stmt = mysqli_prepare($conn, "SELECT expert_id AS id FROM experts WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $role    = 'expert';
    mysqli_stmt_close($stmt);
}

// Only generate a token if the email actually matches an account — but
// either way we redirect to the same page with the same ?sent=1 flag, so
// the response doesn't reveal whether the email exists or not.
if ($account) {
    $token = bin2hex(random_bytes(32));

    // Compute the expiry using MySQL's own clock (not PHP's), so it can
    // never mismatch with the NOW() check in reset-password.php — this
    // avoids a PHP-vs-MySQL server timezone mismatch making tokens look
    // expired the instant they're created.
    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO password_resets (email, role, token, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))"
    );
    mysqli_stmt_bind_param($insertStmt, 'sss', $email, $role, $token);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    // No email server is set up for this project, so the reset link is
    // shown directly on the next page instead of being emailed.
    $_SESSION['reset_link_preview'] =
        (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] .
        dirname($_SERVER['PHP_SELF']) . '/reset-password.php?token=' . $token;
}

mysqli_close($conn);

header('Location: forgot-password.php?sent=1');
exit;
?>
