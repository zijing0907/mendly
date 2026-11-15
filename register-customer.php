<?php
require_once 'includes/helpers.php';

$errorMessages = [
    'missing_fields'     => 'Please fill in all required fields.',
    'weak_password'      => 'Password must contain at least 6 characters.',
    'duplicate_email'    => 'That email address is already registered. Try signing in instead.',
    'photo_invalid_type' => 'Your photo must be a JPG or PNG file (HEIC and other formats are not supported).',
    'photo_too_large'    => 'Your photo must be smaller than 5MB.',
    'photo_upload_failed'=> 'We could not save your photo. Please try a different file.',
    'photo_folder_error' => 'The server could not access the photo upload folder. Please tell your dev team (this usually means assets/uploads/ needs to be created or made writable).',
    'server_error'       => 'Something went wrong on our end. Please try again.',
];

$formError = isset($_GET['error'])
    ? ($errorMessages[$_GET['error']] ?? 'Something went wrong. Please try again.')
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Customer Registration | Mendly</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/auth-header.css">
    <link rel="stylesheet" href="assets/css/register-customer.css">
</head>

<body>

<?php include("includes/header.php"); ?>

<main class="registration-container">

    <section class="intro-section">

        <h2>Create Your<br>Customer Account</h2>

        <p>
            Join Mendly to discover trusted repair experts,
            save your favourite services and submit repair requests.
        </p>

        <div class="benefit">
            <h3>Browse Repair Services</h3>
            <p>
                Explore different repair categories and find
                suitable experts.
            </p>
        </div>

        <div class="benefit">
            <h3>Save Favourites</h3>
            <p>
                Keep your preferred repair services for future reference.
            </p>
        </div>

        <div class="benefit">
            <h3>Track Repair Requests</h3>
            <p>
                View and follow the progress of your submitted requests.
            </p>
        </div>

    </section>

    <section class="form-section">

        <form
            id="customerRegistrationForm"
            class="registration-form"
            method="post"
            action="save-customer.php"
            enctype="multipart/form-data"
            novalidate
        >

            <h2>Customer Registration</h2>

            <p class="form-description">
                Fill in your details to create a Mendly customer account.
            </p>

            <?php if ($formError): ?>
                <p class="form-error-banner"><?php echo htmlspecialchars($formError); ?></p>
            <?php endif; ?>

            <div class="form-group photo-group">
                <label>Profile Photo</label>

                <div class="photo-upload">

                    <label for="avatar" class="photo-preview" id="photoPreview">
                        <span id="photoPlaceholder">🙂</span>
                        <img id="photoPreviewImage" alt="" hidden>
                        <span class="photo-camera">📷</span>
                    </label>

                    <div class="photo-upload-info">
                        <p>Add a photo so experts can recognize you (optional).</p>

                        <label for="avatar" class="upload-button" id="avatarUploadLabel">
                            Upload Photo
                        </label>

                        <button
                            type="button"
                            id="removeAvatarBtn"
                            class="remove-photo-button"
                            hidden
                        >
                            Remove
                        </button>

                        <input
                            type="file"
                            id="avatar"
                            name="avatar"
                            accept="image/png, image/jpeg"
                            hidden
                        >

                        <small class="photo-hint">JPG, PNG up to 5MB</small>

                        <small
                            id="avatarSuccess"
                            class="photo-success"
                        ></small>

                        <small
                            id="avatarError"
                            class="error-message"
                        ></small>
                    </div>

                </div>
            </div>

            <div class="form-group">
                <label for="fullName">Full Name</label>

                <input
                    type="text"
                    id="fullName"
                    name="fullName"
                    placeholder="Enter your full name"
                >

                <small
                    id="fullNameError"
                    class="error-message"
                ></small>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                >

                <small
                    id="emailError"
                    class="error-message"
                ></small>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="Example: 0123456789"
                >

                <small
                    id="phoneError"
                    class="error-message"
                ></small>
            </div>

            <div class="form-group">
                <label for="location">Location</label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="Enter your city or area"
                >

                <small
                    id="locationError"
                    class="error-message"
                ></small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <div class="password-container">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                    >

                    <button
                        type="button"
                        id="showPassword"
                    >
                        Show
                    </button>
                </div>

                <small
                    id="passwordError"
                    class="error-message"
                ></small>
            </div>

            <div class="form-group">
                <label for="confirmPassword">
                    Confirm Password
                </label>

                <div class="password-container">
                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirmPassword"
                        placeholder="Enter your password again"
                    >

                    <button
                        type="button"
                        id="showConfirmPassword"
                    >
                        Show
                    </button>
                </div>

                <small
                    id="confirmPasswordError"
                    class="error-message"
                ></small>
            </div>

            <div class="terms-group">
                <label>
                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                    >

                    I agree to Mendly's terms and conditions.
                </label>

                <small
                    id="termsError"
                    class="error-message"
                ></small>
            </div>

            <button
                type="submit"
                class="create-button"
            >
                Create Customer Account →
            </button>

            <p
                id="successMessage"
                class="success-message"
            ></p>

            <p class="back-link">
                <a href="create-account.php">
                    ← Back to account type selection
                </a>
            </p>

            <p class="sign-in-text">
                Already have an account?

                <a href="login.php">
                    Login
                </a>
            </p>

        </form>

    </section>

</main>

<?php include("includes/footer.php"); ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/register-customer.js"></script>

</body>
</html>
