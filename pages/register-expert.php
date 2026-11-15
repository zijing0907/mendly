<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

$errorMessages = [
    'missing_fields'      => 'Please fill in all required fields.',
    'weak_password'       => 'Password must contain at least 6 characters.',
    'duplicate_email'     => 'That email address is already registered. Try signing in instead.',
    'photo_invalid_type'  => 'Your photo must be a JPG or PNG file (HEIC and other formats are not supported).',
    'photo_too_large'     => 'Your photo must be smaller than 5MB.',
    'photo_upload_failed' => 'We could not save your photo. Please try a different file.',
    'photo_folder_error'  => 'The server could not access the photo upload folder. Please tell your dev team (this usually means assets/uploads/ needs to be created or made writable).',
    'server_error'        => 'Something went wrong on our end. Please try again.',
];

$formError = isset($_GET['error'])
    ? ($errorMessages[$_GET['error']] ?? 'Something went wrong. Please try again.')
    : null;
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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Repair Expert Registration | Mendly</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/auth-header.css">
    <link rel="stylesheet" href="assets/css/register-expert.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="registration-container">

    <section class="intro-section">

        <h2>
            Repair,<br>Don’t Replace.
        </h2>

        <p>
            Mendly connects you with trusted repair experts who
            bring new life to the items you love.
        </p>

        <div class="intro-perks">

            <div class="perk">
                <div class="perk-icon">🛡</div>
                <h3>Grow Your Business</h3>
                <p>Reach more customers and expand your repair business.</p>
            </div>

            <div class="perk">
                <div class="perk-icon">★</div>
                <h3>Showcase Your Skills</h3>
                <p>Highlight your expertise and build trust with your work.</p>
            </div>

            <div class="perk">
                <div class="perk-icon">🌿</div>
                <h3>Sustainable Impact</h3>
                <p>Be part of a community that values repair and reduces waste.</p>
            </div>

        </div>

    </section>

    <section class="form-section">

        <p class="back-link">
            <a href="pages/create-account.php">
                ← Back to Account Type
            </a>
        </p>

        <form
            id="expertRegistrationForm"
            class="registration-form"
            method="post"
            action="actions/save-expert.php"
            enctype="multipart/form-data"
            novalidate
        >
            <?php echo csrfField(); ?>

            <h2>Repair Expert Registration 🌿</h2>

            <p class="form-description">
                Create your expert account to get started.
            </p>

            <?php if ($formError): ?>
                <p class="form-error-banner"><?php echo htmlspecialchars($formError); ?></p>
            <?php endif; ?>

            <div class="form-group photo-group">
                <label>Profile / Business Photo</label>

                <div class="photo-upload">

                    <label for="businessPhoto" class="photo-preview" id="photoPreview">
                        <span id="photoPlaceholder">🏬</span>
                        <img id="photoPreviewImage" alt="" hidden>
                        <span class="photo-camera">📷</span>
                    </label>

                    <div class="photo-upload-info">
                        <p>Upload a photo of your business or workshop.</p>

                        <label for="businessPhoto" class="upload-button" id="businessPhotoUploadLabel">
                            Upload Photo
                        </label>

                        <button
                            type="button"
                            id="removeBusinessPhotoBtn"
                            class="remove-photo-button"
                            hidden
                        >
                            Remove
                        </button>

                        <input
                            type="file"
                            id="businessPhoto"
                            name="businessPhoto"
                            accept="image/png, image/jpeg"
                            hidden
                        >

                        <small class="photo-hint">JPG, PNG up to 5MB</small>

                        <small
                            id="businessPhotoSuccess"
                            class="photo-success"
                        ></small>

                        <small
                            id="businessPhotoError"
                            class="error-message"
                        ></small>
                    </div>

                </div>
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="fullName">
                        Full Name
                    </label>

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
                    <label for="businessName">
                        Business or Workshop Name
                    </label>

                    <input
                        type="text"
                        id="businessName"
                        name="businessName"
                        placeholder="Enter your business or workshop name"
                    >

                    <small
                        id="businessNameError"
                        class="error-message"
                    ></small>
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="email">
                        Email Address
                    </label>

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
                    <label for="phone">
                        Phone Number
                    </label>

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

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="password">
                        Password
                    </label>

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

            </div>

            <div class="form-group">
                <label for="experience">
                    Years of Experience
                </label>

                <select
                    id="experience"
                    name="experience"
                >
                    <option value="">
                        Select experience
                    </option>

                    <option value="0-1">Less than 1 year</option>
                    <option value="1-3">1 – 3 years</option>
                    <option value="3-5">3 – 5 years</option>
                    <option value="5-10">5 – 10 years</option>
                    <option value="10+">10+ years</option>
                </select>

                <small
                    id="experienceError"
                    class="error-message"
                ></small>
            </div>

            <div class="form-group">
                <label for="location">
                    Business Location
                </label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    placeholder="City, State or Country"
                >

                <small
                    id="locationError"
                    class="error-message"
                ></small>
            </div>

            <div class="form-group">
                <label for="description">
                    Short Professional Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="300"
                    placeholder="Tell customers about your skills, experience and services..."
                ></textarea>

                <small class="character-count">
                    <span id="characterCount">0</span>/300
                </small>

                <small
                    id="descriptionError"
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

                    I agree to Mendly's Terms and Conditions and
                    Privacy Policy.
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
                Create Repair Expert Account →
            </button>

            <p
                id="successMessage"
                class="success-message"
            ></p>

            <p class="sign-in-text">
                Already have an account?

                <a href="pages/login.php">
                    Sign In
                </a>
            </p>

        </form>

    </section>

</main>

<div class="trust-strip">

    <div class="trust-item">
        <span class="trust-icon">🔒</span>
        <div>
            <h4>Secure & Private</h4>
            <p>Your data is always protected.</p>
        </div>
    </div>

    <div class="trust-item">
        <span class="trust-icon">✅</span>
        <div>
            <h4>Verified Community</h4>
            <p>Only trusted experts.</p>
        </div>
    </div>

    <div class="trust-item">
        <span class="trust-icon">❤</span>
        <div>
            <h4>Support Local</h4>
            <p>Empowering independent craftspeople.</p>
        </div>
    </div>

    <div class="trust-item">
        <span class="trust-icon">🎧</span>
        <div>
            <h4>Help & Support</h4>
            <p>We're here to help you.</p>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/register-expert.js"></script>

</body>
</html>
