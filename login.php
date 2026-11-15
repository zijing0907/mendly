<?php
require_once 'includes/helpers.php';

$errorMessages = [
    'missing_fields'      => 'Please enter both your email and password.',
    'invalid_credentials' => 'That email or password doesn\'t match our records.',
];

$formError      = $errorMessages[$_GET['error'] ?? ''] ?? null;
$justRegistered = isset($_GET['registered']);
$justReset      = isset($_GET['reset']);
$justDeleted    = isset($_GET['deleted']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mendly Login</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>

    <?php include "includes/header.php"; ?>

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

            <form id="loginForm" class="login-form" method="post" action="process-login.php" novalidate>

                <h2>Login</h2>

                <h3>Welcome Back!</h3>

                <p>Sign in to continue to your account.</p>

                <?php if ($formError): ?>
                    <p class="form-error-banner"><?php echo htmlspecialchars($formError); ?></p>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email">

                    <small id="emailError" class="error-message"></small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="password-container">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password">

                        <button type="button" id="showPassword">
                            Show
                        </button>
                    </div>

                    <small id="passwordError" class="error-message"></small>
                </div>

                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>

                    <a href="forgot-password.php">Forgot password?</a>
                </div>

                <button type="submit" class="sign-in-button">
                    Login →
                </button>

                <p id="successMessage" class="success-message">
                    <?php if ($justRegistered): ?>
                        Account registered successfully! Please sign in to continue.
                    <?php elseif ($justReset): ?>
                        Password reset successfully! Please sign in with your new password.
                    <?php elseif ($justDeleted): ?>
                        Your account has been deleted. We're sorry to see you go.
                    <?php endif; ?>
                </p>

                <p class="create-account">
                    Don’t have an account?
                    <a href="create-account.php"> Register Here</a>
                </p>

            </form>

        </section>

    </main>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/login.js"></script>

</body>

</html>