<?php
session_start();

$pageTitle = "My Repair Requests";

include 'includes/db.php';

// Customers only. Without the role check an expert would see the requests
// of whichever customer happens to share their numeric user_id.
$customerID = requireRole('customer');

// Every repair request this customer has made, newest first, with the
// review (if any) so completed jobs can link straight to it.
$sql = "
    SELECT 
        rq.request_id,
        rq.item_name,
        rq.brand,
        rq.repair_description,
        rq.additional_notes,
        rq.quoted_price,
        rq.status,
        rq.request_date,
        s.service_id,
        s.title AS service_title,
        s.starting_price,
        e.full_name AS expert_name,
        e.business_name,
        r.review_id,
        COALESCE(
            (
                SELECT image_path
                FROM service_images
                WHERE service_id = s.service_id
                ORDER BY display_order ASC
                LIMIT 1
            ),
            c.image_path
        ) AS service_thumbnail
    FROM repair_requests rq
    INNER JOIN services s ON rq.service_id = s.service_id
    INNER JOIN experts e ON s.expert_id = e.expert_id
    INNER JOIN categories c ON s.category_id = c.category_id
    LEFT JOIN reviews r ON r.request_id = rq.request_id
    WHERE rq.customer_id = ?
    ORDER BY rq.request_id DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $customerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$requests = [];
while ($row = mysqli_fetch_assoc($result)) {
    $requests[] = $row;
}

// Map a request status to its badge label + CSS class
function getStatusBadge($status = 'Pending')
{
    $currentStatus = trim($status ?? 'Pending');
    switch ($currentStatus) {
        case 'Accepted':
            return ['label' => 'Accepted', 'class' => 'badge-accepted'];
        case 'In Progress':
            return ['label' => 'In Progress', 'class' => 'badge-progress'];
        case 'Ready for Collection':
            return ['label' => 'Ready for Collection', 'class' => 'badge-ready'];
        case 'Completed':
            return ['label' => 'Completed', 'class' => 'badge-completed'];
        case 'Cancelled':
            return ['label' => 'Cancelled', 'class' => 'badge-cancelled'];
        case 'Pending':
        default:
            return ['label' => 'Pending', 'class' => 'badge-pending'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/my-requests.css">
</head>

<body>

    <?php include "includes/header.php"; ?>

    <main class="my-requests-container">

        <div class="page-header">
            <h1>My Repair Requests</h1>
            <p>Track and manage your submitted repair service requests.</p>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert-success">
                🎉 <strong>Success!</strong> Your repair request has been submitted. The expert will review it shortly.
            </div>
        <?php elseif (isset($_GET['cancelled'])): ?>
            <div class="alert-success">Your request has been cancelled.</div>
        <?php elseif (isset($_GET['cancel_error'])): ?>
            <div class="alert-error">That request can no longer be cancelled — the expert has already started on it.</div>
        <?php endif; ?>

        <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $req): ?>
                <?php
                $badge = getStatusBadge($req['status']);
                $thumbnail = !empty($req['service_thumbnail']) ? $req['service_thumbnail'] : 'assets/images/logo.png';
                $createdDate = date('M d, Y', strtotime($req['request_date']));
                ?>
                <div class="request-card">
                    <div class="card-header">
                        <div class="request-info">
                            <strong><?php echo htmlspecialchars($req['item_name']); ?></strong>
                            <?php if (!empty($req['brand'])): ?>
                                <span>(<?php echo htmlspecialchars($req['brand']); ?>)</span>
                            <?php endif; ?>
                            &bull; <small>Submitted on <?php echo $createdDate; ?></small>
                        </div>
                        <span class="status-badge <?php echo $badge['class']; ?>">
                            <?php echo $badge['label']; ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <img
                            src="<?php echo htmlspecialchars($thumbnail); ?>"
                            alt="<?php echo htmlspecialchars($req['service_title']); ?>"
                            class="service-img"
                            onerror="this.src='assets/images/logo.png';">
                        <div class="details-wrapper">
                            <h3 class="service-title"><?php echo htmlspecialchars($req['service_title']); ?></h3>
                            <div class="expert-meta">
                                Handled by <strong><?php echo htmlspecialchars($req['expert_name']); ?></strong> &bull;
                                Starting Price: <strong>RM <?php echo number_format($req['starting_price'], 2); ?></strong>
                                <?php if ($req['quoted_price'] !== null): ?>
                                    &bull; Quoted: <strong>RM <?php echo number_format($req['quoted_price'], 2); ?></strong>
                                <?php endif; ?>
                            </div>

                            <div class="desc-box">
                                <strong>Problem Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($req['repair_description'])); ?>

                                <?php if (!empty($req['additional_notes'])): ?>
                                    <br><br><strong>Additional Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($req['additional_notes'])); ?>
                                <?php endif; ?>
                            </div>

                            <div class="request-actions">
                                <a href="service-details.php?id=<?php echo (int) $req['service_id']; ?>" class="btn-link">View service</a>

                                <?php if ($req['status'] === 'Completed'): ?>
                                    <a href="review.php" class="btn-link btn-link-solid">
                                        <?php echo $req['review_id'] ? 'View your review' : 'Write a review'; ?>
                                    </a>
                                <?php elseif ($req['status'] === 'Pending'): ?>
                                    <form method="post" action="cancel-request.php" onsubmit="return confirm('Cancel this repair request?');">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $req['request_id']; ?>">
                                        <button type="submit" class="btn-link btn-link-danger">Cancel request</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No Repair Requests Found</h3>
                <p>You haven't submitted any repair requests yet.</p>
                <a href="browse-services.php" class="btn-browse">Browse Repair Services</a>
            </div>
        <?php endif; ?>

    </main>

    <?php include "includes/footer.php"; ?>

</body>

</html>