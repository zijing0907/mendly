<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()

require_once __DIR__ . '/../includes/db.php';

// Creates a new service (serviceId = 0) or updates one of the expert's own
// services, including its photos: new uploads are added, ticked photos are
// removed. Everything is validated before anything is written.

$expertId = requireRole('expert');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/add-service.php');
}

const MAX_SERVICE_PHOTOS = 6;
const MAX_PHOTO_BYTES    = 5 * 1024 * 1024;

$serviceId         = (int) ($_POST['serviceId'] ?? 0);   // 0 = new service
$categoryId        = trim($_POST['categoryId'] ?? '');
$title             = trim($_POST['title'] ?? '');
$description       = trim($_POST['description'] ?? '');
$startingPrice     = trim($_POST['startingPrice'] ?? '');
$estimatedDuration = trim($_POST['estimatedDuration'] ?? '');
$removePhotoIds    = array_map('intval', (array) ($_POST['removePhotos'] ?? []));

// Send the expert back to the form (staying in edit mode) with an error
function backWithError(int $serviceId, string $error): never
{
    redirect('pages/add-service.php?' . ($serviceId ? 'id=' . $serviceId . '&' : '') . 'error=' . $error);
}

// Editing: the service must belong to this expert
$existingPhotos = [];
if ($serviceId > 0) {
    $ownerStmt = mysqli_prepare($conn, "SELECT service_id FROM services WHERE service_id = ? AND expert_id = ?");
    mysqli_stmt_bind_param($ownerStmt, 'ii', $serviceId, $expertId);
    mysqli_stmt_execute($ownerStmt);
    $isOwner = mysqli_fetch_assoc(mysqli_stmt_get_result($ownerStmt)) !== null;

    if (!$isOwner) {
        redirect('pages/my-services.php?msg=not_found');
    }

    $photoStmt = mysqli_prepare($conn, "SELECT image_id, image_path, display_order FROM service_images WHERE service_id = ?");
    mysqli_stmt_bind_param($photoStmt, 'i', $serviceId);
    mysqli_stmt_execute($photoStmt);
    $existingPhotos = mysqli_fetch_all(mysqli_stmt_get_result($photoStmt), MYSQLI_ASSOC);
}

// --- Validate the text fields --------------------------------------------

if ($categoryId === '' || $title === '' || $startingPrice === '' || $estimatedDuration === '') {
    backWithError($serviceId, 'missing_fields');
}

// Confirm the submitted category actually exists, rather than trusting
// whatever numeric value was posted
$categoryId = (int) $categoryId;
$checkCategoryStmt = mysqli_prepare($conn, "SELECT category_id FROM categories WHERE category_id = ?");
mysqli_stmt_bind_param($checkCategoryStmt, 'i', $categoryId);
mysqli_stmt_execute($checkCategoryStmt);
if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkCategoryStmt))) {
    backWithError($serviceId, 'invalid_category');
}

// Starting price must be a positive number
if (!is_numeric($startingPrice) || (float) $startingPrice <= 0) {
    backWithError($serviceId, 'invalid_price');
}
$startingPrice = (float) $startingPrice;

// --- Validate the photos ----------------------------------------------------

// Only photos that really belong to this service can be removed
$existingIds    = array_map('intval', array_column($existingPhotos, 'image_id'));
$removePhotoIds = array_values(array_intersect($removePhotoIds, $existingIds));

// New uploads waiting to be saved: [temp file, safe extension]
$newPhotos = [];
$files     = $_FILES['servicePhotos'] ?? null;

if ($files && is_array($files['name'])) {
    foreach ($files['tmp_name'] as $i => $tmpName) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] > MAX_PHOTO_BYTES) {
            backWithError($serviceId, 'invalid_photo');
        }
        // Extension comes from the real image type, never the filename
        $extension = safeImageExtension($tmpName);
        if ($extension === null) {
            backWithError($serviceId, 'invalid_photo');
        }
        $newPhotos[] = [$tmpName, $extension];
    }
}

$photoCount = count($existingPhotos) - count($removePhotoIds) + count($newPhotos);
if ($photoCount > MAX_SERVICE_PHOTOS) {
    backWithError($serviceId, 'too_many_photos');
}

// --- Save -----------------------------------------------------------------

if ($serviceId > 0) {
    // The expert_id condition means an expert can only ever update their
    // own listing, whatever service ID is posted.
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE services
         SET category_id = ?, title = ?, description = ?, starting_price = ?, estimated_duration = ?
         WHERE service_id = ? AND expert_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'issdsii', $categoryId, $title, $description, $startingPrice, $estimatedDuration, $serviceId, $expertId);
    $successUrl = 'pages/my-services.php?msg=updated';
} else {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO services (expert_id, category_id, title, description, starting_price, estimated_duration)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'iissds', $expertId, $categoryId, $title, $description, $startingPrice, $estimatedDuration);
    $successUrl = 'pages/add-service.php?added=1';
}

if (!mysqli_stmt_execute($stmt)) {
    backWithError($serviceId, 'server_error');
}
if ($serviceId === 0) {
    $serviceId = mysqli_insert_id($conn);
}

// Remove ticked photos. Only files the experts uploaded (under
// uploads/services/) are deleted from disk; the demo photos in
// assets/images are shared and stay.
foreach ($existingPhotos as $photo) {
    if (!in_array((int) $photo['image_id'], $removePhotoIds, true)) {
        continue;
    }
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM service_images WHERE image_id = ? AND service_id = ?");
    mysqli_stmt_bind_param($deleteStmt, 'ii', $photo['image_id'], $serviceId);
    mysqli_stmt_execute($deleteStmt);

    if (strpos($photo['image_path'], 'uploads/services/') === 0) {
        @unlink(dirname(__DIR__) . '/' . $photo['image_path']);
    }
}

// Add new photos after the existing ones
if ($newPhotos) {
    $uploadDir = dirname(__DIR__) . '/uploads/services/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $order = 1;
    foreach ($existingPhotos as $photo) {
        $order = max($order, (int) $photo['display_order'] + 1);
    }

    $insertPhoto = mysqli_prepare($conn, "INSERT INTO service_images (service_id, image_path, caption, display_order) VALUES (?, ?, ?, ?)");
    foreach ($newPhotos as [$tmpName, $extension]) {
        $fileName = 'service_' . $serviceId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
            $path = 'uploads/services/' . $fileName;
            mysqli_stmt_bind_param($insertPhoto, 'issi', $serviceId, $path, $title, $order);
            mysqli_stmt_execute($insertPhoto);
            $order++;
        }
    }
}

redirect($successUrl);
