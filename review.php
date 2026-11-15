<?php
// Write A Review page.
// Previously this rendered a hardcoded $artisans list and review.js kept
// everything client-side only, so nothing survived a page reload. Now:
//   - the "awaiting feedback" list is real: every one of this customer's
//     COMPLETED repair requests that doesn't have a review yet.
//   - requests that already have a review render straight into the
//     locked "submitted" view, pulling the saved rating/tags/text/media.
//   - review.js posts new reviews to submit-review.php (see that file).

session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Reviews are left by customers about the experts who did the work.
if (($_SESSION['role'] ?? '') !== 'customer') {
  header('Location: profile.php');
  exit;
}

include("includes/db.php");

$customerId = (int) $_SESSION['user_id'];

// Every completed repair request for this customer, with its review (if
// one has already been submitted) left-joined on.
$sql = "SELECT rq.request_id, rq.item_name, e.expert_id, e.full_name AS expert_name,
               r.review_id, r.rating, r.comment, r.review_date
        FROM repair_requests rq
        INNER JOIN services s ON rq.service_id = s.service_id
        INNER JOIN experts e ON s.expert_id = e.expert_id
        LEFT JOIN reviews r ON r.request_id = rq.request_id
        WHERE rq.customer_id = ? AND rq.status = 'Completed'
        ORDER BY (r.review_id IS NULL) DESC, rq.request_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $customerId);
mysqli_stmt_execute($stmt);
$requests = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Batch-load tags + media for any requests that already have a review,
// keyed by review_id, instead of querying per-card in the loop below.
$reviewIds = array_values(array_filter(array_column($requests, 'review_id')));
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

mysqli_close($conn);

$tags = ["Craftsmanship", "Communication", "Packaging", "Value", "Shipping speed"];

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Write A Review</title>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600&family=Work+Sans:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/review.css">
</head>

<body>

  <?php include("includes/header.php"); ?>

  <div class="page">
    <div class="header" id="header">
      <h1>Write A Review</h1>
      <p id="subcount"></p>
    </div>

    <div class="list" id="list">

      <?php if (!$requests): ?>
        <p class="review-empty">
          You don't have any completed repairs yet. Once a repair request
          is marked <strong>Completed</strong>, you'll be able to leave a
          review for it here.
        </p>
      <?php endif; ?>

      <?php foreach ($requests as $req): ?>
        <?php
        $isSubmitted = $req['review_id'] !== null;
        $reviewTags  = $isSubmitted ? ($tagsByReview[$req['review_id']] ?? []) : [];
        $reviewMedia = $isSubmitted ? ($mediaByReview[$req['review_id']] ?? []) : [];
        ?>
        <div
          class="card"
          data-request-id="<?= $req['request_id'] ?>"
          <?= $isSubmitted ? 'data-submitted="1"' : '' ?>>
          <div class="artisan">
            <div class="pfp"><?= htmlspecialchars(initials($req['expert_name'])) ?></div>
            <div class="artisan-name"><?= htmlspecialchars($req['expert_name']) ?></div>
          </div>

          <div class="content">

            <!-- Editable review form. Hidden once a review has been submitted. -->
            <div class="editable" <?= $isSubmitted ? 'hidden' : '' ?>>
              <div class="rating-row">
                <p class="label">Overall rating:</p>
                <div class="stars" data-rating="0">
                  <?php for ($n = 1; $n <= 5; $n++): ?>
                    <span class="star" data-value="<?= $n ?>">&#9733;</span>
                  <?php endfor; ?>
                </div>
              </div>
              <p class="error-msg rating-error">Select a rating before submitting.</p>

              <p class="label">Content:</p>
              <div class="tags-row">Description tags:
                <div class="tags">
                  <?php foreach ($tags as $tag): ?>
                    <button type="button" class="tag"><?= htmlspecialchars($tag) ?></button>
                  <?php endforeach; ?>
                </div>
              </div>

              <textarea class="review-box" placeholder="Review the artisan to help others"></textarea>
              <p class="error-msg">Enter a review before submitting.</p>

              <div class="media-row">
                <button type="button" class="upload-btn" aria-label="Upload media">+</button>
                <input type="file" class="media-file-input" accept="image/*,video/*" multiple hidden>
                <div class="media-thumbs"></div>
                <span class="media-count">0/3 upload media</span>
              </div>
              <p class="error-msg media-error">Files must be 15MB or smaller.</p>

              <div class="submit-row"><button class="submit-btn">Submit review</button></div>
            </div>

            <!-- Read-only view shown once a review is submitted (or already exists in the DB). -->
            <div class="submitted-view" <?= $isSubmitted ? '' : 'hidden' ?>>
              <p class="thank-you show"><span class="check">&#10003;</span>Thanks for reviewing <?= htmlspecialchars($req['expert_name']) ?>!</p>
              <p class="review-date">
                <?= $isSubmitted ? 'Reviewed on ' . date('d M Y', strtotime($req['review_date'])) : '' ?>
              </p>
              <button type="button" class="see-review-btn">See your review</button>

              <div class="summary" hidden>
                <p class="summary-label">Overall rating:</p>
                <div class="summary-stars">
                  <?php for ($n = 1; $n <= 5; $n++): ?>
                    <span class="star <?= ($isSubmitted && $n <= $req['rating']) ? 'filled' : '' ?>">&#9733;</span>
                  <?php endfor; ?>
                </div>
                <div class="summary-tags">
                  <?php foreach ($reviewTags as $t): ?>
                    <span class="tag active"><?= htmlspecialchars($t) ?></span>
                  <?php endforeach; ?>
                </div>
                <p class="summary-text"><?= nl2br(htmlspecialchars($req['comment'] ?? '')) ?></p>
                <div class="summary-media">
                  <?php foreach ($reviewMedia as $m): ?>
                    <div class="thumb">
                      <div class="thumb-media">
                        <?php if ($m['media_type'] === 'video'): ?>
                          <video src="<?= htmlspecialchars($m['media_path']) ?>" muted></video>
                          <span class="play-icon">&#9658;</span>
                        <?php else: ?>
                          <img src="<?= htmlspecialchars($m['media_path']) ?>" alt="">
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script src="assets/js/review.js"></script>

  <?php include("includes/footer.php"); ?>
</body>

</html>