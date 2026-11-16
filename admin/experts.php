<?php
// Admin: review new repair experts before customers can see them.
//
// New expert sign-ups start as 'pending' (actions/save-expert.php). Their
// services stay hidden from Browse Services, the Experts page and the AI
// helper until an admin approves them here. Approving also gives them the
// Verified badge; rejecting records a reason that the expert sees on their
// My Services page. If email is configured, the expert is emailed either way.
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// --- Approve / reject --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expertId = (int) ($_POST['expert_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $reason   = mb_substr(trim($_POST['reason'] ?? ''), 0, 255);

    if (!in_array($decision, ['approve', 'reject'], true) || $expertId <= 0) {
        header('Location: experts.php');
        exit;
    }

    $lookup = mysqli_prepare($conn, "SELECT full_name, email FROM experts WHERE expert_id = ? AND approval_status = 'pending'");
    mysqli_stmt_bind_param($lookup, 'i', $expertId);
    mysqli_stmt_execute($lookup);
    $expert = mysqli_fetch_assoc(mysqli_stmt_get_result($lookup));

    if ($expert) {
        if ($decision === 'approve') {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE experts SET approval_status = 'approved', verified = TRUE,
                        rejection_reason = NULL, reviewed_at = NOW()
                 WHERE expert_id = ? AND approval_status = 'pending'"
            );
            mysqli_stmt_bind_param($stmt, 'i', $expertId);
            $subject = 'Your Mendly expert account is approved';
            $body    = "Hi {$expert['full_name']},\n\nGood news: your Mendly expert profile has been approved. "
                . "Your services are now visible to customers.\n\nManage them here: " . appUrl('pages/my-services.php')
                . "\n\n- The Mendly team\n";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE experts SET approval_status = 'rejected', verified = FALSE,
                        rejection_reason = ?, reviewed_at = NOW()
                 WHERE expert_id = ? AND approval_status = 'pending'"
            );
            $reasonOrNull = $reason !== '' ? $reason : null;
            mysqli_stmt_bind_param($stmt, 'si', $reasonOrNull, $expertId);
            $subject = 'About your Mendly expert application';
            $body    = "Hi {$expert['full_name']},\n\nThank you for applying to Mendly. Unfortunately we couldn't approve "
                . "your expert profile this time." . ($reason !== '' ? "\n\nReason: {$reason}" : '')
                . "\n\nIf you have questions, reply through our Contact page: " . appUrl('pages/contact.php')
                . "\n\n- The Mendly team\n";
        }
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) === 1 && mailConfigured()) {
            sendMail($expert['email'], $subject, $body);
        }
    }

    header('Location: experts.php?done=' . ($decision === 'approve' ? 'approved' : 'rejected'));
    exit;
}

// --- Lists ---------------------------------------------------------------------
$pending = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT expert_id, full_name, email, phone, location, business_name, experience, description,
            photo_path, photo_path_mime, created_at
     FROM experts WHERE approval_status = 'pending' ORDER BY created_at"
), MYSQLI_ASSOC);

// Services each pending expert has set up so far
$servicesByExpert = [];
if ($pending) {
    $result = mysqli_query(
        $conn,
        "SELECT s.expert_id, s.title, s.starting_price, c.category_name
         FROM services s
         INNER JOIN categories c ON c.category_id = s.category_id
         INNER JOIN experts e ON e.expert_id = s.expert_id
         WHERE e.approval_status = 'pending'
         ORDER BY s.service_id"
    );
    foreach (mysqli_fetch_all($result, MYSQLI_ASSOC) as $row) {
        $servicesByExpert[$row['expert_id']][] = $row;
    }
}

$recent = mysqli_fetch_all(mysqli_query(
    $conn,
    "SELECT full_name, business_name, approval_status, rejection_reason, reviewed_at
     FROM experts WHERE reviewed_at IS NOT NULL ORDER BY reviewed_at DESC LIMIT 10"
), MYSQLI_ASSOC);

$experienceLabels = ['0-1' => 'Less than 1 year', '1-3' => '1-3 years', '3-5' => '3-5 years', '5-10' => '5-10 years', '10+' => '10+ years'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Experts to Review</title>
    <link rel="stylesheet" href="../<?php echo asset('assets/css/admin.css'); ?>">
</head>

<body>
    <div class="container">
        <?php $adminPage = 'experts'; include __DIR__ . '/../includes/admin-nav.php'; ?>

        <h2>Experts to Review</h2>
        <p class="expert-review-meta">New experts stay hidden from customers until they're approved here.</p>

        <?php if (isset($_GET['done'])): ?>
            <p class="notice">Expert <?php echo $_GET['done'] === 'approved' ? 'approved: their services are now live.' : 'rejected.'; ?></p>
        <?php endif; ?>

        <?php if (!$pending): ?>
            <p class="empty-state">No experts are waiting for review.</p>
        <?php endif; ?>

        <?php foreach ($pending as $expert): ?>
            <?php $photo = imageDataUri($expert['photo_path'], $expert['photo_path_mime']); ?>
            <article class="expert-review-card">
                <?php if ($photo): ?>
                    <img class="expert-review-photo" src="<?php echo e($photo); ?>" alt="Business photo">
                <?php else: ?>
                    <div class="expert-review-photo expert-review-initials"><?php echo e(nameInitials($expert['full_name'])); ?></div>
                <?php endif; ?>

                <div>
                    <h3><?php echo e($expert['business_name']); ?></h3>
                    <p class="expert-review-meta">
                        <?php echo e($expert['full_name']); ?> &middot; <?php echo e($expert['location']); ?>
                        &middot; <?php echo e($experienceLabels[$expert['experience']] ?? $expert['experience']); ?> experience
                        <br>
                        <?php echo e($expert['email']); ?> &middot; <?php echo e($expert['phone']); ?>
                        &middot; signed up <?php echo date('d M Y', strtotime($expert['created_at'])); ?>
                    </p>

                    <?php if ($expert['description']): ?>
                        <p><?php echo nl2br(e($expert['description'])); ?></p>
                    <?php endif; ?>

                    <?php $services = $servicesByExpert[$expert['expert_id']] ?? []; ?>
                    <?php if ($services): ?>
                        <strong>Services set up so far:</strong>
                        <ul class="expert-review-services">
                            <?php foreach ($services as $service): ?>
                                <li><?php echo e($service['title']); ?> (<?php echo e($service['category_name']); ?>), from RM<?php echo number_format((float) $service['starting_price'], 0); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="expert-review-meta">No services set up yet.</p>
                    <?php endif; ?>

                    <div class="expert-review-actions">
                        <form method="post" action="experts.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="expert_id" value="<?php echo (int) $expert['expert_id']; ?>">
                            <input type="hidden" name="decision" value="approve">
                            <button type="submit" class="btn-approve">Approve</button>
                        </form>

                        <form method="post" action="experts.php" onsubmit="return confirm('Reject this expert?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="expert_id" value="<?php echo (int) $expert['expert_id']; ?>">
                            <input type="hidden" name="decision" value="reject">
                            <input type="text" name="reason" maxlength="255" placeholder="Reason (shown to the expert)" aria-label="Reason for rejecting">
                            <button type="submit" class="btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($recent): ?>
            <h3>Recent decisions</h3>
            <table>
                <thead>
                    <tr><th>Expert</th><th>Decision</th><th>Reason</th><th>When</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><strong><?php echo e($row['business_name']); ?></strong><br><small><?php echo e($row['full_name']); ?></small></td>
                            <td><span class="decision-badge decision-<?php echo e($row['approval_status']); ?>"><?php echo e(ucfirst($row['approval_status'])); ?></span></td>
                            <td><?php echo e($row['rejection_reason'] ?: '-'); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($row['reviewed_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>
