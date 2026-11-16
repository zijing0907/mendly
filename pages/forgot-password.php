<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

$waitMinutes   = max(1, (int) ($_GET['wait'] ?? 15));
$errorMessages = [
    'missing_email' => 'Please enter your email address.',
    'too_many'      => "Too many reset requests for that email. Please wait {$waitMinutes} minute"
        . ($waitMinutes === 1 ? '' : 's') . ' and try again.',
];

$formError = $errorMessages[$_GET['error'] ?? ''] ?? null;
$linkGenerated = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password | Mendly</title>

    <link rel="stylesheet" href="<?php echo asset('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/login.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/forgot-password.css'); ?>">
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

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

            <form id="forgotPasswordForm" class="login-form" method="post" action="actions/send-reset-link.php" novalidate>
                <?php echo csrfField(); ?>

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
                        <h3>Check Your Email</h3>

                        <p class="success-message">
                            If an account exists for that email, we've sent it a link to reset your password.
                            The link expires in 30 minutes.
                        </p>
                    <?php endif; ?>

                    <p class="create-account">
                        <a href="pages/forgot-password.php">Send another link</a>
                        &nbsp;·&nbsp;
                        <a href="pages/login.php">Back to Login</a>
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
                        <a href="pages/login.php">Back to Login</a>
                    </p>

                <?php endif; ?>

            </form>

        </section>

    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="<?php echo asset('assets/js/main.js'); ?>"></script>
    <script src="<?php echo asset('assets/js/forgot-password.js'); ?>"></script>

</body>

</html>