<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

// Must be logged in to update a profile
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('pages/login.php'));
    exit;
}

include __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/profile.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] ?? 'customer';

// Collect and trim the submitted fields
$fullName = trim($_POST['fullName'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');

if ($fullName === '' || $email === '' || $phone === '' || $location === '') {
    header('Location: ' . url('pages/profile.php?profile_error=missing_fields'));
    exit;
}

// Full name: letters (incl. accented), spaces, apostrophes, hyphens and
// periods only — no digits or other symbols.
if (!preg_match("/^[\p{L}\s'.-]+$/u", $fullName)) {
    header('Location: ' . url('pages/profile.php?profile_error=invalid_name'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . url('pages/profile.php?profile_error=invalid_email'));
    exit;
}

if (strlen($phone) < 9 || !preg_match('/^[0-9+\-\s]+$/', $phone)) {
    header('Location: ' . url('pages/profile.php?profile_error=invalid_phone'));
    exit;
}

if ($role === 'expert') {
    $businessName   = trim($_POST['businessName'] ?? '');
    $experience     = trim($_POST['experience'] ?? '');
    $description    = trim($_POST['description'] ?? '');

    if ($businessName === '' || $experience === '') {
        header('Location: ' . url('pages/profile.php?profile_error=missing_fields'));
        exit;
    }
}

// Check whether this email is already used by a DIFFERENT account, in
// either table (own current row is excluded from its own table's check)
if ($role === 'expert') {
    $ownTableCheck   = "SELECT expert_id FROM experts WHERE email = ? AND expert_id != ?";
    $otherTableCheck = "SELECT customer_id FROM customers WHERE email = ?";
} else {
    $ownTableCheck   = "SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?";
    $otherTableCheck = "SELECT expert_id FROM experts WHERE email = ?";
}

$checkOwnStmt = mysqli_prepare($conn, $ownTableCheck);
mysqli_stmt_bind_param($checkOwnStmt, 'si', $email, $userId);
mysqli_stmt_execute($checkOwnStmt);
mysqli_stmt_store_result($checkOwnStmt);
$emailTaken = mysqli_stmt_num_rows($checkOwnStmt) > 0;
mysqli_stmt_close($checkOwnStmt);

if (!$emailTaken) {
    $checkOtherStmt = mysqli_prepare($conn, $otherTableCheck);
    mysqli_stmt_bind_param($checkOtherStmt, 's', $email);
    mysqli_stmt_execute($checkOtherStmt);
    mysqli_stmt_store_result($checkOtherStmt);
    $emailTaken = mysqli_stmt_num_rows($checkOtherStmt) > 0;
    mysqli_stmt_close($checkOtherStmt);
}

if ($emailTaken) {
    header('Location: ' . url('pages/profile.php?profile_error=duplicate_email'));
    exit;
}

if ($role === 'expert') {
    // Only touch the photo if a new one was actually uploaded — otherwise
    // keep whatever is already stored. The photo itself gets stored
    // directly in the database (as binary data), not saved as a file on
    // disk. $photoData/$photoMime stay null unless a new photo arrives,
    // and the same photo is kept in sync across both photo_path (business
    // photo) and avatar (nav dropdown) columns.
    $photoData = null;
    $photoMime = null;

    if (isset($_FILES['businessPhoto']) && $_FILES['businessPhoto']['error'] !== UPLOAD_ERR_NO_FILE) {

        // Something was actually selected — if it failed to arrive at all
        // (too big for php.ini's limits, etc.), tell the user why instead
        // of silently keeping their old photo with no explanation.
        if ($_FILES['businessPhoto']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrorCode = $_FILES['businessPhoto']['error'];

            if ($uploadErrorCode === UPLOAD_ERR_INI_SIZE || $uploadErrorCode === UPLOAD_ERR_FORM_SIZE) {
                header('Location: ' . url('pages/profile.php?profile_error=photo_too_large'));
            } else {
                header('Location: ' . url('pages/profile.php?profile_error=photo_upload_failed'));
            }
            exit;
        }

        // getimagesize() reads the actual image header bytes to confirm
        // this really is a JPEG/PNG — it's built into PHP core, unlike
        // mime_content_type()/finfo, which silently return false (and so
        // silently skip the whole upload) if the fileinfo extension isn't
        // enabled on a given machine.
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        $imageInfo          = @getimagesize($_FILES['businessPhoto']['tmp_name']);

        if ($imageInfo === false || !in_array($imageInfo[2], $allowedImageTypes, true)) {
            // Not a real JPEG/PNG (e.g. a HEIC photo straight off an
            // iPhone, or a renamed non-image file).
            header('Location: ' . url('pages/profile.php?profile_error=photo_invalid_type'));
            exit;
        }

        if ($_FILES['businessPhoto']['size'] > 5 * 1024 * 1024) {
            header('Location: ' . url('pages/profile.php?profile_error=photo_too_large'));
            exit;
        }

        $photoData = file_get_contents($_FILES['businessPhoto']['tmp_name']);
        $photoMime = image_type_to_mime_type($imageInfo[2]);

        if ($photoData === false) {
            header('Location: ' . url('pages/profile.php?profile_error=photo_upload_failed'));
            exit;
        }
    }

    if ($photoData !== null) {
        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE experts
             SET full_name = ?, email = ?, phone = ?, location = ?,
                 business_name = ?, experience = ?, description = ?,
                 photo_path = ?, photo_path_mime = ?, avatar = ?, avatar_mime = ?
             WHERE expert_id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            'sssssssssssi',
            $fullName,
            $email,
            $phone,
            $location,
            $businessName,
            $experience,
            $description,
            $photoData,
            $photoMime,
            $photoData,
            $photoMime,
            $userId
        );
    } else {
        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE experts
             SET full_name = ?, email = ?, phone = ?, location = ?,
                 business_name = ?, experience = ?, description = ?
             WHERE expert_id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            'sssssssi',
            $fullName,
            $email,
            $phone,
            $location,
            $businessName,
            $experience,
            $description,
            $userId
        );
    }
} else {
    // Only touch the photo if a new one was actually uploaded — otherwise
    // keep whatever is already stored.
    $avatarData = null;
    $avatarMime = null;

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {

        // Something was actually selected — if it failed to arrive at all
        // (too big for php.ini's limits, etc.), tell the user why instead
        // of silently keeping their old photo with no explanation.
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrorCode = $_FILES['avatar']['error'];

            if ($uploadErrorCode === UPLOAD_ERR_INI_SIZE || $uploadErrorCode === UPLOAD_ERR_FORM_SIZE) {
                header('Location: ' . url('pages/profile.php?profile_error=photo_too_large'));
            } else {
                header('Location: ' . url('pages/profile.php?profile_error=photo_upload_failed'));
            }
            exit;
        }

        // getimagesize() reads the actual image header bytes to confirm
        // this really is a JPEG/PNG — it's built into PHP core, unlike
        // mime_content_type()/finfo, which silently return false (and so
        // silently skip the whole upload) if the fileinfo extension isn't
        // enabled on a given machine.
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        $imageInfo          = @getimagesize($_FILES['avatar']['tmp_name']);

        if ($imageInfo === false || !in_array($imageInfo[2], $allowedImageTypes, true)) {
            // Not a real JPEG/PNG (e.g. a HEIC photo straight off an
            // iPhone, or a renamed non-image file).
            header('Location: ' . url('pages/profile.php?profile_error=photo_invalid_type'));
            exit;
        }

        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            header('Location: ' . url('pages/profile.php?profile_error=photo_too_large'));
            exit;
        }

        $avatarData = file_get_contents($_FILES['avatar']['tmp_name']);
        $avatarMime = image_type_to_mime_type($imageInfo[2]);

        if ($avatarData === false) {
            header('Location: ' . url('pages/profile.php?profile_error=photo_upload_failed'));
            exit;
        }
    }

    if ($avatarData !== null) {
        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE customers SET full_name = ?, email = ?, phone = ?, location = ?, avatar = ?, avatar_mime = ? WHERE customer_id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            'ssssssi',
            $fullName,
            $email,
            $phone,
            $location,
            $avatarData,
            $avatarMime,
            $userId
        );
    } else {
        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE customers SET full_name = ?, email = ?, phone = ?, location = ? WHERE customer_id = ?"
        );

        mysqli_stmt_bind_param($updateStmt, 'ssssi', $fullName, $email, $phone, $location, $userId);
    }
}

if (mysqli_stmt_execute($updateStmt)) {
    // Keep the session's display name (and avatar, if it just changed) in
    // sync with the new values, so the nav dropdown updates immediately
    // instead of waiting for the next login.
    $_SESSION['full_name'] = $fullName;

    if ($role === 'expert' && $photoData !== null) {
        $_SESSION['avatar'] = 'data:' . $photoMime . ';base64,' . base64_encode($photoData);
    } elseif ($role === 'customer' && $avatarData !== null) {
        $_SESSION['avatar'] = 'data:' . $avatarMime . ';base64,' . base64_encode($avatarData);
    }

    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/profile.php?updated=1'));
    exit;
} else {
    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);

    header('Location: ' . url('pages/profile.php?profile_error=server_error'));
    exit;
}
