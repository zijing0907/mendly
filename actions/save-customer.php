<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
include __DIR__ . '/../includes/db.php';

// Only accept form submissions, not direct visits
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/register-customer.php'));
    exit;
}

// Collect and trim the submitted fields
$fullName = trim($_POST['fullName'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');
$password = $_POST['password'] ?? '';

// Basic server-side validation (the form already validates on the client,
// this is the safety net in case someone bypasses it)
if ($fullName === '' || $email === '' || $phone === '' || $location === '' || $password === '') {
    header('Location: ' . url('pages/register-customer.php?error=missing_fields'));
    exit;
}

if (strlen($password) < 6) {
    header('Location: ' . url('pages/register-customer.php?error=weak_password'));
    exit;
}

// Check whether this email is already registered (as a customer or an expert)
$checkStmt = mysqli_prepare($conn, "SELECT customer_id FROM customers WHERE email = ?");
mysqli_stmt_bind_param($checkStmt, 's', $email);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

$emailTaken = mysqli_stmt_num_rows($checkStmt) > 0;
mysqli_stmt_close($checkStmt);

if (!$emailTaken) {
    $checkExpertStmt = mysqli_prepare($conn, "SELECT expert_id FROM experts WHERE email = ?");
    mysqli_stmt_bind_param($checkExpertStmt, 's', $email);
    mysqli_stmt_execute($checkExpertStmt);
    mysqli_stmt_store_result($checkExpertStmt);

    $emailTaken = mysqli_stmt_num_rows($checkExpertStmt) > 0;
    mysqli_stmt_close($checkExpertStmt);
}

if ($emailTaken) {
    header('Location: ' . url('pages/register-customer.php?error=duplicate_email'));
    exit;
}

// Handle the optional avatar upload — the photo itself gets stored
// directly in the database (as binary data), not saved as a file on
// disk. $avatarData holds the raw image bytes, $avatarMime remembers
// whether it's a JPEG or PNG so it can be served back correctly later.
$avatarData = null;
$avatarMime = null;

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {

    // Something was actually selected — if it failed to arrive at all
    // (too big for php.ini's limits, etc.), tell the user why instead of
    // silently registering them with no photo.
    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrorCode = $_FILES['avatar']['error'];

        if ($uploadErrorCode === UPLOAD_ERR_INI_SIZE || $uploadErrorCode === UPLOAD_ERR_FORM_SIZE) {
            header('Location: ' . url('pages/register-customer.php?error=photo_too_large'));
        } else {
            header('Location: ' . url('pages/register-customer.php?error=photo_upload_failed'));
        }
        exit;
    }

    // getimagesize() reads the actual image header bytes to confirm this
    // really is a JPEG/PNG — it's built into PHP core, unlike
    // mime_content_type()/finfo, which silently return false (and so
    // silently skip the whole upload) if the fileinfo extension isn't
    // enabled on a given machine.
    $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
    $imageInfo          = @getimagesize($_FILES['avatar']['tmp_name']);

    if ($imageInfo === false || !in_array($imageInfo[2], $allowedImageTypes, true)) {
        // Not a real JPEG/PNG (e.g. a HEIC photo straight off an iPhone,
        // or a renamed non-image file) — tell the user instead of
        // silently dropping their photo.
        header('Location: ' . url('pages/register-customer.php?error=photo_invalid_type'));
        exit;
    }

    if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
        header('Location: ' . url('pages/register-customer.php?error=photo_too_large'));
        exit;
    }

    $avatarData = file_get_contents($_FILES['avatar']['tmp_name']);
    $avatarMime = image_type_to_mime_type($imageInfo[2]);

    if ($avatarData === false) {
        header('Location: ' . url('pages/register-customer.php?error=photo_upload_failed'));
        exit;
    }
}

// Never store plain-text passwords — hash it first
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert the new customer account
$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO customers (full_name, email, phone, location, password, avatar, avatar_mime)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $insertStmt,
    'sssssss',
    $fullName,
    $email,
    $phone,
    $location,
    $hashedPassword,
    $avatarData,
    $avatarMime
);

if (mysqli_stmt_execute($insertStmt)) {
    mysqli_stmt_close($insertStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/login.php?registered=1'));
    exit;
} else {
    mysqli_stmt_close($insertStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/register-customer.php?error=server_error'));
    exit;
}
