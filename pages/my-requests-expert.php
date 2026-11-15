<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
// Expert-side view of every repair request tied to their services, with a
// status control per request. Mirrors pages/my-requests.php's read-only customer
// view, but adds the ability to advance status (which fires the automated
// chat message via api/update-request-status.php).


require_once __DIR__ . '/../includes/db.php';

$expertId    = requireRole('expert');
$currentPage = 'expert_requests';

$sql = "SELECT rq.request_id, rq.item_name, rq.brand, rq.repair_description,
               rq.additional_notes, rq.status, rq.request_date,
               c.full_name AS customer_name, s.title AS service_title,
               rq.quoted_price,
               -- The customer's own photo of the item if they uploaded one,
               -- otherwise the service / category photo
               COALESCE(
                 (SELECT image_path FROM request_images WHERE request_id = rq.request_id ORDER BY display_order LIMIT 1),
                 (SELECT image_path FROM service_images WHERE service_id = s.service_id ORDER BY display_order LIMIT 1),
                 cat.image_path
               ) AS thumbnail
        FROM repair_requests rq
        INNER JOIN services s ON rq.service_id = s.service_id
        INNER JOIN categories cat ON s.category_id = cat.category_id
        INNER JOIN customers c ON rq.customer_id = c.customer_id
        WHERE s.expert_id = ?
        ORDER BY (rq.status IN ('Completed', 'Cancelled')) ASC, rq.request_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $expertId);
mysqli_stmt_execute($stmt);
$requests = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
mysqli_close($conn);

// What an expert is allowed to move a request to, from its current status.
$nextSteps = [
  'Pending'              => ['Accepted', 'Cancelled'],
  'Accepted'             => ['In Progress', 'Cancelled'],
  'In Progress'          => ['Ready for Collection', 'Cancelled'],
  'Ready for Collection' => ['Completed'],
  'Completed'            => [],
  'Cancelled'            => [],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <!-- All relative links on this page resolve from the project root -->
  <base href="<?php echo e(url()); ?>">
  <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Requests | Mendly</title>
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/my-requests-expert.css">
</head>

<body>

  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="requests-container">

    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <span>&rsaquo;</span>
      <span class="breadcrumb-current">Manage Requests</span>
    </nav>

    <div class="requests-heading">
      <h2>Repair Requests</h2>
      <p>Review incoming requests and update their status as you work on them.</p>
    </div>

    <?php if (!$requests): ?>
      <p class="requests-empty">No repair requests yet.</p>
    <?php endif; ?>

    <div class="requests-list">
      <?php foreach ($requests as $req): ?>
        <?php $options = $nextSteps[$req['status']] ?? []; ?>
        <article class="request-card" data-request-id="<?php echo $req['request_id']; ?>">

          <img
            class="request-thumb"
            src="<?php echo htmlspecialchars($req['thumbnail'] ?: 'assets/images/logo.png'); ?>"
            alt="<?php echo htmlspecialchars($req['item_name']); ?>">

          <div class="request-body">
            <div class="request-top">
              <h3><?php echo htmlspecialchars($req['item_name']); ?><?php echo $req['brand'] ? ' (' . htmlspecialchars($req['brand']) . ')' : ''; ?></h3>
              <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $req['status'])); ?>">
                <?php echo htmlspecialchars($req['status']); ?>
              </span>
            </div>

            <p class="request-meta">
              For <?php echo htmlspecialchars($req['service_title']); ?>
              &middot; by <?php echo htmlspecialchars($req['customer_name']); ?>
              &middot; <?php echo date('d M Y', strtotime($req['request_date'])); ?>
            </p>

            <p class="request-desc"><?php echo nl2br(htmlspecialchars($req['repair_description'])); ?></p>

            <?php if ($req['additional_notes']): ?>
              <p class="request-notes">Notes: <?php echo nl2br(htmlspecialchars($req['additional_notes'])); ?></p>
            <?php endif; ?>

            <?php if ($options): ?>
              <div class="request-actions">
                <select class="status-select">
                  <option value="">Update status&hellip;</option>
                  <?php foreach ($options as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="btn-solid confirm-status-btn" disabled>Update</button>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script src="assets/js/my-requests-expert.js"></script>

</body>

</html>