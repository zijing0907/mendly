<?php
session_start();

$errorMessages = [
    'missing_email' => 'Please enter your email address.',
];

$formError = $errorMessages[$_GET['error'] ?? ''] ?? null;
$linkGenerated = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password | Mendly</title>

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

            <form id="forgotPasswordForm" class="login-form" method="post" action="send-reset-link.php" novalidate>

                <h2>Forgot Password</h2>

                <?php if ($linkGenerated): ?>

                    <?php if (!empty($_SESSION['reset_link_preview'])): ?>
                        <h3>Reset Link Generated</h3>

                        <div class="reset-link-box">
                            <p>
                                Here's your reset link
                                <span class="reset-link-note">(shown here instead of emailed, for demo purposes)</span>:
                            </p>

                            <a href="<?php echo htmlspecialchars($_SESSION['reset_link_preview']); ?>">
                                <?php echo htmlspecialchars($_SESSION['reset_link_preview']); ?>
                            </a>
                        </div>

                        <?php unset($_SESSION['reset_link_preview']); ?>
                    <?php else: ?>
                        <h3>Check Your Details</h3>

                        <p class="success-message">
                            If an account exists for that email, a reset link has been generated.
                        </p>
                    <?php endif; ?>

                    <p class="create-account">
                        <a href="forgot-password.php">Send another link</a>
                        &nbsp;·&nbsp;
                        <a href="login.php">Back to Login</a>
                    </p>

                <?php else: ?>

                    <h3>Reset Your Password</h3>

                    <p>Enter your email and we'll help you get back in.</p>

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

                    <button type="submit" class="sign-in-button">
                        Send Reset Link →
                    </button>

                    <p class="create-account">
                        Remembered your password?
                        <a href="login.php">Back to Login</a>
                    </p>

                <?php endif; ?>

            </form>

        </section>

    </main>

    <?php include("includes/footer.php"); ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/forgot-password.js"></script>

</body>

</html>