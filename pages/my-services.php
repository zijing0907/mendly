<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
// Expert dashboard for their own service listings: see every listing with
// its request count, edit it, take it off Browse Services (inactive), or
// delete it outright if no customer has requested it yet.
require_once __DIR__ . '/../includes/db.php';

$expertId    = requireRole('expert');
$currentPage = 'my_services';

$sql = "SELECT s.service_id, s.title, s.description, s.starting_price, s.estimated_duration,
               s.status, s.created_at, c.category_name,
               COALESCE(
                 (SELECT image_path FROM service_images WHERE service_id = s.service_id ORDER BY display_order LIMIT 1),
                 c.image_path
               ) AS thumbnail,
               (SELECT COUNT(*) FROM repair_requests rq WHERE rq.service_id = s.service_id) AS total_requests,
               (SELECT COUNT(*) FROM repair_requests rq
                 WHERE rq.service_id = s.service_id
                   AND rq.status NOT IN ('Completed', 'Cancelled')) AS open_requests
        FROM services s
        INNER JOIN categories c ON s.category_id = c.category_id
        WHERE s.expert_id = ?
        ORDER BY s.status = 'active' DESC, s.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $expertId);
mysqli_stmt_execute($stmt);
$services = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
mysqli_close($conn);

$messages = [
    'added'       => ['success', 'Service added — it is now live on Browse Services.'],
    'updated'     => ['success', 'Service updated.'],
    'activated'   => ['success', 'Service is live again on Browse Services.'],
    'deactivated' => ['success', 'Service hidden from Browse Services. Existing requests are unaffected.'],
    'deleted'     => ['success', 'Service deleted.'],
    'has_requests' => ['error', 'This service has repair requests linked to it, so it can\'t be deleted. Deactivate it instead to hide it from customers.'],
    'not_found'   => ['error', 'That service could not be found.'],
];
$flash = $messages[$_GET['msg'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- All relative links on this page resolve from the project root -->
    <base href="<?php echo e(url()); ?>">
    <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services | Mendly</title>
    <link rel="stylesheet" href="<?php echo asset('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/my-requests-expert.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/my-services.css'); ?>">
</head>

<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="requests-container">

        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>&rsaquo;</span>
            <span class="breadcrumb-current">My Services</span>
        </nav>

        <div class="requests-heading services-heading">
            <div>
                <h2>My Services</h2>
                <p>Manage the repair services customers can find and request.</p>
            </div>
            <a href="pages/add-service.php" class="btn-primary">+ Add a Service</a>
        </div>

        <?php if ($flash): ?>
            <p class="flash flash-<?php echo $flash[0]; ?>"><?php echo e($flash[1]); ?></p>
        <?php endif; ?>

        <?php if (!$services): ?>
            <p class="requests-empty">
                You haven't listed any services yet. <a href="pages/add-service.php">Add your first one</a> so customers can find you.
            </p>
        <?php endif; ?>

        <div class="requests-list">
            <?php foreach ($services as $service): ?>
                <?php $isActive = $service['status'] === 'active'; ?>
                <article class="request-card <?php echo $isActive ? '' : 'is-inactive'; ?>">

                    <img class="request-thumb" src="<?php echo e($service['thumbnail'] ?: 'assets/images/logo.png'); ?>" alt="<?php echo e($service['title']); ?>">

                    <div class="request-body">
                        <div class="request-top">
                            <h3><?php echo e($service['title']); ?></h3>
                            <span class="status-badge <?php echo $isActive ? 'status-completed' : 'status-cancelled'; ?>">
                                <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>

                        <p class="request-meta">
                            <?php echo e($service['category_name']); ?>
                            &middot; from RM <?php echo number_format((float) $service['starting_price'], 2); ?>
                            &middot; <?php echo e($service['estimated_duration']); ?>
                        </p>

                        <p class="request-desc"><?php echo nl2br(e($service['description'])); ?></p>

                        <p class="request-meta">
                            <?php echo (int) $service['total_requests']; ?> request<?php echo (int) $service['total_requests'] === 1 ? '' : 's'; ?>
                            <?php if ((int) $service['open_requests'] > 0): ?>
                                (<a href="pages/my-requests-expert.php"><?php echo (int) $service['open_requests']; ?> open</a>)
                            <?php endif; ?>
                        </p>

                        <div class="service-actions">
                            <?php if ($isActive): ?>
                                <a href="pages/service-details.php?id=<?php echo (int) $service['service_id']; ?>" class="btn-outline-small">View listing</a>
                            <?php endif; ?>
                            <a href="pages/add-service.php?id=<?php echo (int) $service['service_id']; ?>" class="btn-outline-small">Edit</a>

                            <form method="post" action="actions/service-action.php">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="service_id" value="<?php echo (int) $service['service_id']; ?>">
                                <input type="hidden" name="action" value="<?php echo $isActive ? 'deactivate' : 'activate'; ?>">
                                <button type="submit" class="btn-outline-small"><?php echo $isActive ? 'Deactivate' : 'Activate'; ?></button>
                            </form>

                            <?php if ((int) $service['total_requests'] === 0): ?>
                                <form method="post" action="actions/service-action.php" onsubmit="return confirm('Delete this service permanently?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="service_id" value="<?php echo (int) $service['service_id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-outline-small btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>

</html>
