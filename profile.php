<?php
session_start();

// Must be logged in to view this page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include("includes/db.php");

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'customer';

// Fetch the account details from the correct table for this role
if ($role === 'expert') {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT full_name, email, phone, location, created_at, avatar, avatar_mime,
                business_name, experience, description, photo_path, photo_path_mime
         FROM experts WHERE expert_id = ?"
    );
} else {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT full_name, email, phone, location, created_at, avatar, avatar_mime
         FROM customers WHERE customer_id = ?"
    );
}

mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// If the account no longer exists (e.g. deleted), sign them out
if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$user['role'] = $role;

// The avatar/business photo are stored as raw binary data in the
// database, not file paths — build them into data URIs (a text string
// with the whole image encoded inline) so they can be used directly as
// <img src="..."> without a separate image-serving script.
if (!empty($user['avatar']) && !empty($user['avatar_mime'])) {
    $user['avatar'] = 'data:' . $user['avatar_mime'] . ';base64,' . base64_encode($user['avatar']);
} else {
    $user['avatar'] = null;
}

if ($role === 'expert' && !empty($user['photo_path']) && !empty($user['photo_path_mime'])) {
    $user['photo_path'] = 'data:' . $user['photo_path_mime'] . ';base64,' . base64_encode($user['photo_path']);
} else {
    $user['photo_path'] = null;
}

// Business details already came back on the same row for experts
$expert = $role === 'expert' ? $user : null;

mysqli_close($conn);

$experienceLabels = [
    '0-1'  => 'Less than 1 year',
    '1-3'  => '1 – 3 years',
    '3-5'  => '3 – 5 years',
    '5-10' => '5 – 10 years',
    '10+'  => '10+ years',
];

// Feedback banners after a redirect from update-profile.php / update-password.php
$profileErrorMessages = [
    'missing_fields'      => 'Please fill in all required fields.',
    'invalid_name'        => 'Full name can only contain letters, spaces, hyphens and apostrophes.',
    'invalid_email'       => 'Please enter a valid email address.',
    'invalid_phone'       => 'Please enter a valid phone number (numbers only, at least 9 digits).',
    'duplicate_email'     => 'That email address is already used by another account.',
    'photo_invalid_type'  => 'Your photo must be a JPG or PNG file (HEIC and other formats are not supported).',
    'photo_too_large'     => 'Your photo must be smaller than 5MB.',
    'photo_upload_failed' => 'We could not save your photo. Please try a different file.',
    'photo_folder_error'  => 'The server could not access the photo upload folder. Please tell your dev team (this usually means assets/uploads/ needs to be created or made writable).',
    'server_error'        => 'Something went wrong saving your details. Please try again.',
];

$passwordErrorMessages = [
    'missing_fields'    => 'Please fill in all password fields.',
    'wrong_password'    => 'Your current password is incorrect.',
    'weak_password'     => 'New password must be at least 6 characters.',
    'password_mismatch' => 'New password and confirmation do not match.',
    'server_error'       => 'Something went wrong updating your password. Please try again.',
];

$deleteErrorMessages = [
    'missing_password' => 'Please enter your password to confirm.',
    'wrong_password'    => 'That password is incorrect.',
    'has_requests'       => 'Your account has repair request history linked to it, so it can\'t be deleted. Please contact support if you need help with this.',
    'server_error'       => 'Something went wrong deleting your account. Please try again.',
];

$deleteError = isset($_GET['delete_error'])
    ? ($deleteErrorMessages[$_GET['delete_error']] ?? 'Something went wrong. Please try again.')
    : null;

$profileUpdated  = isset($_GET['updated']);
$profileError    = isset($_GET['profile_error'])
    ? ($profileErrorMessages[$_GET['profile_error']] ?? 'Something went wrong. Please try again.')
    : null;
$passwordUpdated = isset($_GET['password_updated']);
$passwordError   = isset($_GET['password_error'])
    ? ($passwordErrorMessages[$_GET['password_error']] ?? 'Something went wrong. Please try again.')
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

    <title>My Account | Mendly</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/profile.css">
</head>

<body>

<?php include("header.php"); ?>

<main class="profile-container">

    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <span>&rsaquo;</span>
        <span class="breadcrumb-current">My Account</span>
    </nav>

    <div class="profile-heading">
        <h2>My Account <span class="heading-leaf">🌿</span></h2>
        <p>View and update the details linked to your Mendly account.</p>
    </div>

    <div class="profile-card">

        <div class="profile-summary">
            <div class="profile-avatar" id="profileSummaryAvatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Profile photo">
                <?php else: ?>
                    <?php echo htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1))); ?>
                <?php endif; ?>
            </div>

            <div>
                <h3 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h3>

                <span class="role-badge">
                    <?php echo $user['role'] === 'expert' ? 'Repair Expert' : 'Customer'; ?>
                </span>
            </div>
        </div>

        <p class="detail-value member-since">
            Member since <?php echo date('d M Y', strtotime($user['created_at'])); ?>
        </p>

       <div id="accountDetailsView" <?php echo $profileError ? 'style="display:none"' : ''; ?>>

            <div class="section-divider">
                <h4>Account Details</h4>
            </div>

            <?php if ($profileUpdated): ?>
                <p class="success-message">Your account details were updated successfully.</p>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <span class="detail-label">Full Name</span>
                    <p class="detail-value"><?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>

                <div class="form-group">
                    <span class="detail-label">Email Address</span>
                    <p class="detail-value"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <span class="detail-label">Phone Number</span>
                    <p class="detail-value"><?php echo htmlspecialchars($user['phone']); ?></p>
                </div>

                <div class="form-group">
                    <span class="detail-label"><?php echo $expert ? 'Business Location' : 'Location'; ?></span>
                    <p class="detail-value"><?php echo htmlspecialchars($user['location']); ?></p>
                </div>
            </div>

            <?php if ($expert): ?>
                <div class="section-divider">
                    <h4>Business Details</h4>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <span class="detail-label">Business / Workshop Name</span>
                        <p class="detail-value"><?php echo htmlspecialchars($expert['business_name']); ?></p>
                    </div>

                    <div class="form-group">
                        <span class="detail-label">Years of Experience</span>
                        <p class="detail-value"><?php echo htmlspecialchars($experienceLabels[$expert['experience']] ?? $expert['experience']); ?></p>
                    </div>
                </div>

                <div class="form-group">
                    <span class="detail-label">Business Photo</span>
                    <div class="photo-preview">
                        <?php if (!empty($expert['photo_path'])): ?>
                            <img src="<?php echo htmlspecialchars($expert['photo_path']); ?>" alt="Current business photo">
                        <?php else: ?>
                            🏢
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <span class="detail-label">Professional Description</span>
                    <p class="detail-value"><?php echo nl2br(htmlspecialchars($expert['description'] ?? '')); ?></p>
                </div>
            <?php endif; ?>

            <div class="profile-actions">
                <button type="button" id="editDetailsBtn" class="btn-save-solid">Edit Details</button>
            </div>

        </div>

        <form id="accountDetailsForm" action="update-profile.php" method="post" enctype="multipart/form-data" novalidate <?php echo $profileError ? '' : 'style="display:none"'; ?>>

            <div class="section-divider">
                <h4>Account Details</h4>
            </div>

            <?php if ($profileError): ?>
                <p class="error-message banner-error"><?php echo htmlspecialchars($profileError); ?></p>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    <span class="error-message" id="fullNameError"></span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    <span class="error-message" id="emailError"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    <span class="error-message" id="phoneError"></span>
                </div>

                <div class="form-group">
                    <label for="location"><?php echo $expert ? 'Business Location' : 'Location'; ?></label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location']); ?>">
                    <span class="error-message" id="locationError"></span>
                </div>
            </div>

            <?php if ($expert): ?>
                <div class="section-divider">
                    <h4>Business Details</h4>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="businessName">Business / Workshop Name</label>
                        <input type="text" id="businessName" name="businessName" value="<?php echo htmlspecialchars($expert['business_name']); ?>">
                        <span class="error-message" id="businessNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="experience">Years of Experience</label>
                        <select id="experience" name="experience">
                            <?php foreach ($experienceLabels as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"
                                    <?php echo $expert['experience'] === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="businessPhoto">Business Photo</label>

                    <div class="photo-upload">
                        <div class="photo-preview" id="businessPhotoPreview">
                            <?php if (!empty($expert['photo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($expert['photo_path']); ?>" alt="Current business photo">
                            <?php else: ?>
                                🏢
                            <?php endif; ?>
                        </div>

                        <div class="photo-upload-info">
                            <label for="businessPhoto" class="upload-button">Replace Photo</label>
                            <input type="file" id="businessPhoto" name="businessPhoto" accept="image/png, image/jpeg" hidden>
                            <span class="photo-hint">JPG, PNG up to 5MB. Leave empty to keep your current photo.</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Professional Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($expert['description'] ?? ''); ?></textarea>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="avatar">Profile Photo</label>

                    <div class="photo-upload">
                        <div class="photo-preview" id="avatarPreview">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Current profile photo">
                            <?php else: ?>
                                🙂
                            <?php endif; ?>
                        </div>

                        <div class="photo-upload-info">
                            <label for="avatar" class="upload-button">Replace Photo</label>
                            <input type="file" id="avatar" name="avatar" accept="image/png, image/jpeg" hidden>
                            <span class="photo-hint">JPG, PNG up to 5MB. Leave empty to keep your current photo.</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="profile-actions">
                <button type="submit" class="btn-save-solid">Save Changes</button>
                <button type="button" id="cancelEditBtn" class="btn-save-outline">Cancel</button>
            </div>

        </form>

        <form id="changePasswordForm" action="update-password.php" method="post" novalidate>

            <div class="section-divider">
                <h4>Change Password</h4>
            </div>

            <?php if ($passwordUpdated): ?>
                <p class="success-message">Your password was updated successfully.</p>
            <?php elseif ($passwordError): ?>
                <p class="error-message banner-error"><?php echo htmlspecialchars($passwordError); ?></p>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <div class="password-container">
                        <input type="password" id="currentPassword" name="currentPassword">
                        <button type="button" id="toggleCurrentPassword">Show</button>
                    </div>
                    <span class="error-message" id="currentPasswordError"></span>
                </div>

                <div class="form-group"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <div class="password-container">
                        <input type="password" id="newPassword" name="newPassword">
                        <button type="button" id="toggleNewPassword">Show</button>
                    </div>
                    <span class="error-message" id="newPasswordError"></span>
                </div>

                <div class="form-group">
                    <label for="confirmNewPassword">Confirm New Password</label>
                    <div class="password-container">
                        <input type="password" id="confirmNewPassword" name="confirmNewPassword">
                        <button type="button" id="toggleConfirmNewPassword">Show</button>
                    </div>
                    <span class="error-message" id="confirmNewPasswordError"></span>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="btn-save-outline">Update Password</button>
            </div>

        </form>

        <div class="profile-actions logout-actions">
            <a href="logout.php" class="btn-logout-solid">Log Out</a>
        </div>

        <div class="danger-zone">

            <div class="section-divider">
                <h4>Delete Account</h4>
            </div>

            <div id="deleteAccountView" <?php echo $deleteError ? 'style="display:none"' : ''; ?>>

                <p class="danger-zone-warning">
                    Deleting your account is permanent and cannot be undone.
                    All your saved details<?php echo $role === 'customer' ? ' and wishlist' : ' and services'; ?>
                    will be removed.
                </p>

                <div class="profile-actions">
                    <button type="button" id="deleteAccountBtn" class="btn-delete-solid">Delete Account</button>
                </div>

            </div>

            <form id="deleteAccountForm" action="delete-account.php" method="post" novalidate <?php echo $deleteError ? '' : 'style="display:none"'; ?>>

                <?php if ($deleteError): ?>
                    <p class="error-message banner-error"><?php echo htmlspecialchars($deleteError); ?></p>
                <?php endif; ?>

                <div class="form-group">
                    <label for="deletePassword">Enter your password to confirm</label>
                    <div class="password-container">
                        <input type="password" id="deletePassword" name="deletePassword">
                        <button type="button" id="toggleDeletePassword">Show</button>
                    </div>
                    <span class="error-message" id="deletePasswordError"></span>
                </div>

                <div class="profile-actions">
                    <button type="submit" class="btn-delete-solid">Permanently Delete Account</button>
                    <button type="button" id="cancelDeleteBtn" class="btn-save-outline">Cancel</button>
                </div>

            </form>

        </div>

    </div>

</main>

<?php include("footer.php"); ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/profile.js"></script>

</body>
</html>
