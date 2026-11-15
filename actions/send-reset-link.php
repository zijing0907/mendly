<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/forgot-password.php'));
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    header('Location: ' . url('pages/forgot-password.php?error=missing_email'));
    exit;
}

// Check customers first, then experts — same pattern as actions/process-login.php,
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
    // never mismatch with the NOW() check in pages/reset-password.php — this
    // avoids a PHP-vs-MySQL server timezone mismatch making tokens look
    // expired the instant they're created.
    $insertStmt = mysqli_prepare(
        $conn,
        "INSERT INTO password_resets (email, role, token, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
    );
    mysqli_stmt_bind_param($insertStmt, 'sss', $email, $role, $token);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    $resetLink = appUrl('pages/reset-password.php?token=' . $token);

    if (mailConfigured()) {
        // Real mode: email the link. The page says the same thing whether or
        // not this works, so it never reveals which emails have accounts.
        sendMail(
            $email,
            'Reset your Mendly password',
            "Hi,\n\n"
            . "Someone (hopefully you) asked to reset the password for your Mendly account.\n\n"
            . "Choose a new password here:\n$resetLink\n\n"
            . "This link works once and expires in 30 minutes. If you didn't ask for this, "
            . "you can ignore this email and your password stays the same.\n\n"
            . "- The Mendly team\n"
        );
    } else {
        // Demo mode (no MAIL_HOST in .env): show the link on the next page
        // instead. Fine on your own computer, but anyone could reset anyone's
        // password this way, so configure email before putting the site online.
        $_SESSION['reset_link_preview'] = $resetLink;
    }
}

mysqli_close($conn);

header('Location: ' . url('pages/forgot-password.php?sent=1'));
exit;
?>
