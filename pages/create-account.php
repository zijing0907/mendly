<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Create Account | Mendly</title>

    <link rel="stylesheet" href="<?php echo asset('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/create-account.css'); ?>">
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="create-account-container">

        <section class="intro-section">

            <h2>
                Repair,<br>
                Don’t Replace.
            </h2>

            <p>
                Join Mendly and become part of a community that
                values skilled craftsmanship and sustainable repair.
            </p>

            <div class="benefit">
                <h3>Find Trusted Experts</h3>

                <p>
                    Discover skilled repair specialists for your
                    treasured belongings.
                </p>
            </div>

            <div class="benefit">
                <h3>Support Local Artisans</h3>

                <p>
                    Connect with independent craftsmen and small
                    repair businesses.
                </p>
            </div>

            <div class="benefit">
                <h3>Choose Sustainability</h3>

                <p>
                    Repair and restore items instead of replacing them.
                </p>
            </div>

        </section>

        <section class="selection-section">

            <div class="selection-card">

                <h2><span class="title-underline">Create Account</span></h2>

                <h3>Join Mendly 🌿</h3>

                <p class="description">
                    Create an account and be part of our craft
                    repair community.
                </p>

                <h4>I want to join as</h4>

                <button
                    type="button"
                    class="role-card"
                    id="customerCard">
                    <div class="role-icon">
                        <?php echo icon('user'); ?>
                    </div>

                    <div class="role-content">
                        <h3>Customer</h3>

                        <p>
                            Find trusted repair experts for your
                            beloved items.
                        </p>

                        <ul>
                            <li>Browse repair services</li>
                            <li>Save favourites</li>
                            <li>Submit repair requests</li>
                        </ul>
                    </div>

                    <div class="arrow-icon">
                        →
                    </div>
                </button>

                <button
                    type="button"
                    class="role-card"
                    id="expertCard">
                    <div class="role-icon">
                        <?php echo icon('tool'); ?>
                    </div>

                    <div class="role-content">
                        <h3>Repair Expert</h3>

                        <p>
                            Grow your repair business and connect
                            with more customers.
                        </p>

                        <ul>
                            <li>List your repair services</li>
                            <li>Showcase your work</li>
                            <li>Manage repair requests</li>
                        </ul>
                    </div>

                    <div class="arrow-icon">
                        →
                    </div>
                </button>

                <p id="selectionMessage" class="selection-message"></p>

                <p class="sign-in-text">
                    Already have an account?

                    <a href="pages/login.php">
                        Login
                    </a>
                </p>

            </div>

        </section>

    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="<?php echo asset('assets/js/main.js'); ?>"></script>
    <script src="<?php echo asset('assets/js/create-account.js'); ?>"></script>

</body>

</html>