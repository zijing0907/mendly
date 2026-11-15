<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
// Handles the Activate / Deactivate / Delete buttons on pages/my-services.php.
// Every query is scoped to the logged-in expert, so one expert can never
// change another expert's listing by editing the posted service_id.
require_once __DIR__ . '/../includes/db.php';

$expertId = requireRole('expert');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/my-services.php'));
    exit;
}

$serviceId = (int) ($_POST['service_id'] ?? 0);
$action    = $_POST['action'] ?? '';

$ownerStmt = mysqli_prepare($conn, "SELECT service_id FROM services WHERE service_id = ? AND expert_id = ?");
mysqli_stmt_bind_param($ownerStmt, 'ii', $serviceId, $expertId);
mysqli_stmt_execute($ownerStmt);
$owned = mysqli_fetch_assoc(mysqli_stmt_get_result($ownerStmt));
mysqli_stmt_close($ownerStmt);

if (!$owned) {
    header('Location: ' . url('pages/my-services.php?msg=not_found'));
    exit;
}

if ($action === 'activate' || $action === 'deactivate') {
    $newStatus = $action === 'activate' ? 'active' : 'inactive';

    $stmt = mysqli_prepare($conn, "UPDATE services SET status = ? WHERE service_id = ? AND expert_id = ?");
    mysqli_stmt_bind_param($stmt, 'sii', $newStatus, $serviceId, $expertId);
    mysqli_stmt_execute($stmt);

    header('Location: ' . url('pages/my-services.php?msg=' . ($action === 'activate' ? 'activated' : 'deactivated')));
    exit;
}

if ($action === 'delete') {
    // repair_requests keeps a foreign key to services (customers need their
    // history), so a service that has ever been requested can only be
    // deactivated, not deleted.
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM repair_requests WHERE service_id = ?");
    mysqli_stmt_bind_param($countStmt, 'i', $serviceId);
    mysqli_stmt_execute($countStmt);
    $requestCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];

    if ($requestCount > 0) {
        header('Location: ' . url('pages/my-services.php?msg=has_requests'));
        exit;
    }

    // Delete the expert's uploaded photo files. (The service_images and
    // favorites rows themselves cascade via their foreign keys.)
    $photoStmt = mysqli_prepare($conn, "SELECT image_path FROM service_images WHERE service_id = ?");
    mysqli_stmt_bind_param($photoStmt, 'i', $serviceId);
    mysqli_stmt_execute($photoStmt);
    foreach (mysqli_fetch_all(mysqli_stmt_get_result($photoStmt), MYSQLI_ASSOC) as $photo) {
        if (strpos($photo['image_path'], 'uploads/services/') === 0) {
            @unlink(dirname(__DIR__) . '/' . $photo['image_path']);
        }
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM services WHERE service_id = ? AND expert_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $serviceId, $expertId);
    mysqli_stmt_execute($stmt);

    header('Location: ' . url('pages/my-services.php?msg=deleted'));
    exit;
}

header('Location: ' . url('pages/my-services.php'));
exit;
