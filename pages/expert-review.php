<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
// Public "all reviews for this expert" page, reached via ?expert_id=<id>
// from the Experts page, a service's details page, or (for experts) the
// "My Reviews" link in their account menu.


require_once __DIR__ . '/../includes/db.php';

$expertId = (int) ($_GET['expert_id'] ?? 0);

if (!$expertId) {
  header('Location: ' . url('index.php'));
  exit;
}

$expertStmt = mysqli_prepare($conn, "SELECT expert_id, full_name, business_name FROM experts WHERE expert_id = ?");
mysqli_stmt_bind_param($expertStmt, 'i', $expertId);
mysqli_stmt_execute($expertStmt);
$expert = mysqli_fetch_assoc(mysqli_stmt_get_result($expertStmt));
mysqli_stmt_close($expertStmt);

if (!$expert) {
  header('Location: ' . url('index.php'));
  exit;
}

$sql = "SELECT r.review_id, r.rating, r.comment, r.review_date, rq.item_name,
               c.full_name AS reviewer_name
        FROM reviews r
        INNER JOIN repair_requests rq ON r.request_id = rq.request_id
        INNER JOIN services s ON rq.service_id = s.service_id
        INNER JOIN customers c ON rq.customer_id = c.customer_id
        WHERE s.expert_id = ?
        ORDER BY r.review_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $expertId);
mysqli_stmt_execute($stmt);
$reviews = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Batch-load tags + media for every review shown on this page.
$reviewIds = array_column($reviews, 'review_id');
$tagsByReview  = [];
$mediaByReview = [];

if ($reviewIds) {
  $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
  $types        = str_repeat('i', count($reviewIds));

  $tagStmt = mysqli_prepare($conn, "SELECT review_id, tag_name FROM review_tags WHERE review_id IN ($placeholders)");
  mysqli_stmt_bind_param($tagStmt, $types, ...$reviewIds);
  mysqli_stmt_execute($tagStmt);
  foreach (mysqli_fetch_all(mysqli_stmt_get_result($tagStmt), MYSQLI_ASSOC) as $row) {
    $tagsByReview[$row['review_id']][] = $row['tag_name'];
  }

  $mediaStmt = mysqli_prepare($conn, "SELECT review_id, media_path, media_type FROM review_media WHERE review_id IN ($placeholders) ORDER BY display_order");
  mysqli_stmt_bind_param($mediaStmt, $types, ...$reviewIds);
  mysqli_stmt_execute($mediaStmt);
  foreach (mysqli_fetch_all(mysqli_stmt_get_result($mediaStmt), MYSQLI_ASSOC) as $row) {
    $mediaByReview[$row['review_id']][] = $row;
  }
}

$totalReviews  = count($reviews);
$averageRating = $totalReviews > 0 ? array_sum(array_column($reviews, 'rating')) / $totalReviews : 0;

mysqli_close($conn);

// Only use the first 2 words of the name for the avatar initials,
// so unusually long names don't blow up the profile picture.
function initials(string $name): string
{
  $parts = preg_split('/\s+/', trim($name));
  $parts = array_slice($parts, 0, 2);
  return implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $parts));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <!-- All relative links on this page resolve from the project root -->
  <base href="<?php echo e(url()); ?>">
  <meta name="csrf-token" content="<?php echo e(csrfToken()); ?>">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($expert['business_name'] ?: $expert['full_name']); ?> — Reviews | Mendly</title>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600&family=Work+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo asset('assets/css/global.css'); ?>">
  <link rel="stylesheet" href="<?php echo asset('assets/css/review.css'); ?>">
  <link rel="stylesheet" href="<?php echo asset('assets/css/expert-reviews.css'); ?>">
</head>

<body>

  <?php include __DIR__ . '/../includes/header.php'; ?>

  <div class="page">
    <div class="header" id="header">
      <h1><?php echo htmlspecialchars($expert['business_name'] ?: $expert['full_name']); ?></h1>
      <p id="subcount">
        <?php if ($totalReviews > 0): ?>
          <span class="avg-rating">★ <?php echo number_format($averageRating, 1); ?></span>
          from <?php echo $totalReviews; ?> review<?php echo $totalReviews === 1 ? '' : 's'; ?>
        <?php else: ?>
          No reviews yet
        <?php endif; ?>
      </p>
    </div>

    <div class="list" id="list">

      <?php if (!$reviews): ?>
        <p class="review-empty">
          <?php echo htmlspecialchars($expert['full_name']); ?> hasn't received any reviews yet.
        </p>
      <?php endif; ?>

      <?php foreach ($reviews as $rev): ?>
        <?php
        $reviewTags  = $tagsByReview[$rev['review_id']] ?? [];
        $reviewMedia = $mediaByReview[$rev['review_id']] ?? [];
        ?>
        <div class="card expert-review-card">
          <div class="artisan">
            <div class="pfp"><?php echo htmlspecialchars(initials($rev['reviewer_name'])); ?></div>
            <div class="artisan-name"><?php echo htmlspecialchars($rev['reviewer_name']); ?></div>
          </div>

          <div class="content">
            <p class="review-date">
              <?php echo htmlspecialchars($rev['item_name'] ?: 'Repair'); ?>
              &middot; Reviewed on <?php echo date('d M Y', strtotime($rev['review_date'])); ?>
            </p>

            <div class="summary-stars">
              <?php for ($n = 1; $n <= 5; $n++): ?>
                <span class="star <?php echo $n <= $rev['rating'] ? 'filled' : ''; ?>">&#9733;</span>
              <?php endfor; ?>
            </div>

            <?php if ($reviewTags): ?>
              <div class="summary-tags">
                <?php foreach ($reviewTags as $t): ?>
                  <span class="tag active"><?php echo htmlspecialchars($t); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <p class="summary-text"><?php echo nl2br(htmlspecialchars($rev['comment'] ?? '')); ?></p>

            <?php if ($reviewMedia): ?>
              <div class="summary-media">
                <?php foreach ($reviewMedia as $m): ?>
                  <div class="thumb">
                    <div class="thumb-media">
                      <?php if ($m['media_type'] === 'video'): ?>
                        <video src="<?php echo htmlspecialchars($m['media_path']); ?>" muted controls></video>
                      <?php else: ?>
                        <img src="<?php echo htmlspecialchars($m['media_path']); ?>" alt="">
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>

</html>