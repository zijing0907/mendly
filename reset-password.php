<?php
include("includes/db.php");

$token = $_GET['token'] ?? '';
$tokenValid = false;

if ($token !== '') {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT reset_id FROM password_resets
         WHERE token = ? AND used = FALSE AND expires_at > NOW()"
    );
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $tokenValid = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) !== null;
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

$errorMessages = [
    'missing_fields'    => 'Please fill in both password fields.',
    'weak_password'     => 'Password must contain at least 6 characters.',
    'password_mismatch' => 'Passwords do not match.',
    'invalid_token'     => 'This reset link is invalid or has expired. Please request a new one.',
    'server_error'      => 'Something went wrong on our end. Please try again.',
];

$formError = $errorMessages[$_GET['error'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reset Password | Mendly</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/forgot-password.css">
</head>

<body>

    <?php include("includes/header.php"); ?>

    <main class="login-container">

        <section class="intro-section">
            <h2>Repair,<br>Don’t Replace.</h2>

            <p>
                Mendly connects you with skilled craft repair experts
                who bring treasured items back to life.
            </p>

            <div class="benefit">
                <h3>Find Trusted Experts</h3>
                <p>Discover specialists in niche repair services.</p>
            </div>

            <div class="benefit">
                <h3>Quality & Care</h3>
                <p>Every repair is completed with care and precision.</p>
            </div>

            <div class="benefit">
                <h3>Sustainable Choice</h3>
                <p>Repair more, waste less and keep items longer.</p>
            </div>
        </section>

        <section class="form-section">

            <?php if (!$tokenValid): ?>

                <div class="login-form">
                    <h2>Reset Password</h2>

                    <p class="form-error-banner">
                        This reset link is invalid or has expired. Please request a new one.
                    </p>

                    <p class="create-account">
                        <a href="forgot-password.php">Request a new reset link</a>
                    </p>
                </div>

            <?php else: ?>

                <form id="resetPasswordForm" class="login-form" method="post" action="update-reset-password.php" novalidate>

                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <h2>Reset Password</h2>

                    <h3>Choose a New Password</h3>

                    <?php if ($formError): ?>
                        <p class="form-error-banner"><?php echo htmlspecialchars($formError); ?></p>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="newPassword">New Password</label>

                        <div class="password-container">
                            <input type="password" id="newPassword" name="newPassword" placeholder="Create a new password">
                            <button type="button" id="showNewPassword">Show</button>
                        </div>

                        <small id="newPasswordError" class="error-message"></small>
                    </div>

                    <div class="form-group">
                        <label for="confirmNewPassword">Confirm New Password</label>

                        <div class="password-container">
                            <input type="password" id="confirmNewPassword" name="confirmNewPassword" placeholder="Enter your new password again">
                            <button type="button" id="showConfirmNewPassword">Show</button>
                        </div>

                        <small id="confirmNewPasswordError" class="error-message"></small>
                    </div>

                    <button type="submit" class="sign-in-button">
                        Reset Password →
                    </button>

                </form>

            <?php endif; ?>

        </section>

    </main>

    <?php include("includes/footer.php"); ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/reset-password.js"></script>

</body>

</html>