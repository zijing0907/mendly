<?php
// Saves a review submitted from review.php: rating + comment go into
// `reviews`, tags into `review_tags`, uploaded photos/videos into
// `review_media`. Called via fetch()/FormData from review.js, so it
// always responds with JSON rather than a redirect.

session_start();
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
  http_response_code(401);
  echo json_encode(['error' => 'Please log in as a customer to leave a review.']);
  exit;
}

$customerId = (int) $_SESSION['user_id'];
$requestId  = (int) ($_POST['request_id'] ?? 0);
$rating     = (int) ($_POST['rating'] ?? 0);
$comment    = trim($_POST['comment'] ?? '');
$tags       = $_POST['tags'] ?? [];
if (!is_array($tags)) {
  $tags = [];
}

if (!$requestId || $rating < 1 || $rating > 5 || $comment === '') {
  http_response_code(400);
  echo json_encode(['error' => 'A rating and review text are required.']);
  exit;
}

// Confirm this repair request actually belongs to the logged-in customer,
// and that it's completed (you can only review finished work).
$checkStmt = prepareOrFail(
  $conn,
  "SELECT request_id FROM repair_requests WHERE request_id = ? AND customer_id = ? AND status = 'Completed'"
);
mysqli_stmt_bind_param($checkStmt, 'ii', $requestId, $customerId);
mysqli_stmt_execute($checkStmt);
if (!mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
  http_response_code(403);
  echo json_encode(['error' => 'This repair request can\'t be reviewed.']);
  exit;
}

// One review per request — reviews.request_id is UNIQUE too, this just
// gives a friendlier error than a raw DB constraint failure.
$existsStmt = prepareOrFail($conn, "SELECT review_id FROM reviews WHERE request_id = ?");
mysqli_stmt_bind_param($existsStmt, 'i', $requestId);
mysqli_stmt_execute($existsStmt);
if (mysqli_fetch_assoc(mysqli_stmt_get_result($existsStmt))) {
  http_response_code(409);
  echo json_encode(['error' => 'You already reviewed this request.']);
  exit;
}

$insertStmt = prepareOrFail($conn, "INSERT INTO reviews (request_id, rating, comment) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($insertStmt, 'iis', $requestId, $rating, $comment);
mysqli_stmt_execute($insertStmt);
$reviewId = mysqli_insert_id($conn);

// --- Tags ---
$savedTags = [];
if ($tags) {
  $tagStmt = prepareOrFail($conn, "INSERT INTO review_tags (review_id, tag_name) VALUES (?, ?)");
  foreach ($tags as $tag) {
    $tag = trim((string) $tag);
    if ($tag === '' || strlen($tag) > 50) {
      continue;
    }
    mysqli_stmt_bind_param($tagStmt, 'is', $reviewId, $tag);
    mysqli_stmt_execute($tagStmt);
    $savedTags[] = $tag;
  }
}

// --- Media (up to 3 files, images or mp4, 15MB cap each — same rule the
// chat widget uses in chat_upload.php) ---
$allowedTypes = [
  'image/jpeg' => ['jpg', 'image'],
  'image/png'  => ['png', 'image'],
  'image/gif'  => ['gif', 'image'],
  'image/webp' => ['webp', 'image'],
  'video/mp4'  => ['mp4', 'video'],
];
$maxBytes  = 15 * 1024 * 1024;
$uploadDir = __DIR__ . '/assets/uploads/reviews/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

$savedMedia = [];

if (isset($_FILES['media'])) {
  $files = $_FILES['media'];
  $count = is_array($files['name']) ? count($files['name']) : 0;

  $mediaStmt = prepareOrFail(
    $conn,
    "INSERT INTO review_media (review_id, media_path, media_type, display_order) VALUES (?, ?, ?, ?)"
  );

  $order = 1;
  for ($i = 0; $i < min($count, 3); $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
      continue;
    }
    if ($files['size'][$i] > $maxBytes) {
      continue;
    }

    $mimeType = mime_content_type($files['tmp_name'][$i]);
    if (!isset($allowedTypes[$mimeType])) {
      continue;
    }

    [$ext, $type] = $allowedTypes[$mimeType];
    $filename = 'review_' . $reviewId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
      $relativePath = 'assets/uploads/reviews/' . $filename;
      mysqli_stmt_bind_param($mediaStmt, 'issi', $reviewId, $relativePath, $type, $order);
      mysqli_stmt_execute($mediaStmt);
      $savedMedia[] = ['path' => $relativePath, 'type' => $type];
      $order++;
    }
  }
}

echo json_encode([
  'success'     => true,
  'review_id'   => $reviewId,
  'request_id'  => $requestId,
  'rating'      => $rating,
  'comment'     => $comment,
  'tags'        => $savedTags,
  'media'       => $savedMedia,
  'review_date' => date('c'),
]);
