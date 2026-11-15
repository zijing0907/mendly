<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
include __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/register-expert.php'));
    exit;
}

// Collect and trim the submitted fields
$fullName       = trim($_POST['fullName'] ?? '');
$businessName   = trim($_POST['businessName'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$password       = $_POST['password'] ?? '';
$experience     = trim($_POST['experience'] ?? '');
$location       = trim($_POST['location'] ?? '');
$description    = trim($_POST['description'] ?? '');

if (
    $fullName === '' || $businessName === '' || $email === '' || $phone === ''
    || $password === '' || $experience === '' || $location === ''
) {
    header('Location: ' . url('pages/register-expert.php?error=missing_fields'));
    exit;
}

if (strlen($password) < 6) {
    header('Location: ' . url('pages/register-expert.php?error=weak_password'));
    exit;
}

// Check whether this email is already registered (as a customer or an expert)
$checkStmt = mysqli_prepare($conn, "SELECT expert_id FROM experts WHERE email = ?");
mysqli_stmt_bind_param($checkStmt, 's', $email);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

$emailTaken = mysqli_stmt_num_rows($checkStmt) > 0;
mysqli_stmt_close($checkStmt);

if (!$emailTaken) {
    $checkCustomerStmt = mysqli_prepare($conn, "SELECT customer_id FROM customers WHERE email = ?");
    mysqli_stmt_bind_param($checkCustomerStmt, 's', $email);
    mysqli_stmt_execute($checkCustomerStmt);
    mysqli_stmt_store_result($checkCustomerStmt);

    $emailTaken = mysqli_stmt_num_rows($checkCustomerStmt) > 0;
    mysqli_stmt_close($checkCustomerStmt);
}

if ($emailTaken) {
    header('Location: ' . url('pages/register-expert.php?error=duplicate_email'));
    exit;
}

// Handle the optional business photo upload — the photo itself gets
// stored directly in the database (as binary data), not saved as a file
// on disk. $photoData holds the raw image bytes, $photoMime remembers
// whether it's a JPEG or PNG so it can be served back correctly later.
// The same photo doubles as both the business photo and the personal
// avatar shown in the nav dropdown, since the upload field is already
// labeled "Profile / Business Photo".
$photoData = null;
$photoMime = null;

if (isset($_FILES['businessPhoto']) && $_FILES['businessPhoto']['error'] !== UPLOAD_ERR_NO_FILE) {

    // Something was actually selected — if it failed to arrive at all
    // (too big for php.ini's limits, etc.), tell the user why instead of
    // silently registering them with no photo.
    if ($_FILES['businessPhoto']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrorCode = $_FILES['businessPhoto']['error'];

        if ($uploadErrorCode === UPLOAD_ERR_INI_SIZE || $uploadErrorCode === UPLOAD_ERR_FORM_SIZE) {
            header('Location: ' . url('pages/register-expert.php?error=photo_too_large'));
        } else {
            header('Location: ' . url('pages/register-expert.php?error=photo_upload_failed'));
        }
        exit;
    }

    // getimagesize() reads the actual image header bytes to confirm this
    // really is a JPEG/PNG — it's built into PHP core, unlike
    // mime_content_type()/finfo, which silently return false (and so
    // silently skip the whole upload) if the fileinfo extension isn't
    // enabled on a given machine.
    $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
    $imageInfo          = @getimagesize($_FILES['businessPhoto']['tmp_name']);

    if ($imageInfo === false || !in_array($imageInfo[2], $allowedImageTypes, true)) {
        // Not a real JPEG/PNG (e.g. a HEIC photo straight off an iPhone,
        // or a renamed non-image file) — tell the user instead of
        // silently dropping their photo.
        header('Location: ' . url('pages/register-expert.php?error=photo_invalid_type'));
        exit;
    }

    if ($_FILES['businessPhoto']['size'] > 5 * 1024 * 1024) {
        header('Location: ' . url('pages/register-expert.php?error=photo_too_large'));
        exit;
    }

    $photoData = file_get_contents($_FILES['businessPhoto']['tmp_name']);
    $photoMime = image_type_to_mime_type($imageInfo[2]);

    if ($photoData === false) {
        header('Location: ' . url('pages/register-expert.php?error=photo_upload_failed'));
        exit;
    }
}

// Never store plain-text passwords — hash it first
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert the expert account (base details + business details in one row).
// first_login_done starts FALSE so their very first login redirects to
// pages/add-service.php to set up a service listing, instead of the home page.
$insertExpertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO experts
        (full_name, email, phone, location, password,
         business_name, experience, description,
         photo_path, photo_path_mime, avatar, avatar_mime, first_login_done)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)"
);

// If the query itself is invalid (e.g. the database schema is out of
// date and one of these columns doesn't exist yet), mysqli_prepare()
// returns false instead of throwing — catch that here with a clear
// message instead of letting bind_param() crash on a non-object below.
if (!$insertExpertStmt) {
    mysqli_close($conn);

    header('Location: ' . url('pages/register-expert.php?error=server_error'));
    exit;
}

mysqli_stmt_bind_param(
    $insertExpertStmt,
    'ssssssssssss',
    $fullName,
    $email,
    $phone,
    $location,
    $hashedPassword,
    $businessName,
    $experience,
    $description,
    $photoData,
    $photoMime,
    $photoData,
    $photoMime
);

if (mysqli_stmt_execute($insertExpertStmt)) {
    mysqli_stmt_close($insertExpertStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/login.php?registered=1'));
    exit;
} else {
    mysqli_stmt_close($insertExpertStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/register-expert.php?error=server_error'));
    exit;
}
