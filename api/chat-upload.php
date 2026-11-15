<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai.php';
/** @var mysqli $conn */

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not logged in']);
  exit;
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] === 'expert' ? 'expert' : 'customer';

$conversationId = (int) ($_POST['conversation_id'] ?? 0);
if (!$conversationId) {
  http_response_code(400);
  echo json_encode(['error' => 'conversation_id required']);
  exit;
}

// Confirm this user actually owns the conversation they're uploading into.
$ownerCol = $role === 'customer' ? 'customer_id' : 'expert_id';
$checkStmt = prepareOrFail($conn, "SELECT conversation_id FROM conversations WHERE conversation_id = ? AND $ownerCol = ?");
mysqli_stmt_bind_param($checkStmt, 'ii', $conversationId, $userId);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
if (!mysqli_fetch_assoc($checkResult)) {
  http_response_code(403);
  echo json_encode(['error' => 'Not your conversation']);
  exit;
}

if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['error' => 'No file uploaded, or the upload failed']);
  exit;
}

$file = $_FILES['attachment'];

// Only images + mp4 video for now, capped at 15MB (raise this alongside
// php.ini's upload_max_filesize / post_max_size if you need bigger files —
// XAMPP's defaults are 2MB/8MB, well under this limit, so check those too).
$allowedTypes = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/gif'  => 'gif',
  'image/webp' => 'webp',
  'video/mp4'  => 'mp4',
];
$maxBytes = 15 * 1024 * 1024;

if ($file['size'] > $maxBytes) {
  http_response_code(400);
  echo json_encode(['error' => 'File too large (max 15MB)']);
  exit;
}

// Trust the file's actual content over its claimed extension/MIME type.
$mimeType = mime_content_type($file['tmp_name']);
if (!isset($allowedTypes[$mimeType])) {
  http_response_code(400);
  echo json_encode(['error' => 'Unsupported file type — images or MP4 video only']);
  exit;
}

// AI safety check for images (skipped if AI is off). Videos aren't checked:
// they're too large to send inline on Gemini's free tier.
if (strpos($mimeType, 'image/') === 0) {
  $rejection = photoRejectionReason($file['tmp_name']);
  if ($rejection !== null) {
    http_response_code(400);
    echo json_encode(['error' => $rejection]);
    exit;
  }
}

$ext = $allowedTypes[$mimeType];
$filename = 'chat_' . $conversationId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$uploadDir = dirname(__DIR__) . '/uploads/chat/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save the uploaded file']);
  exit;
}

// Stored as a relative web path so chat.js can drop it straight into <img src>.
$attachmentPath = 'uploads/chat/' . $filename;

$insertMsgStmt = prepareOrFail($conn, "INSERT INTO messages (conversation_id, sender_type, sender_id, message_text, attachment_path)
                                        VALUES (?, ?, ?, NULL, ?)");
mysqli_stmt_bind_param($insertMsgStmt, 'isis', $conversationId, $role, $userId, $attachmentPath);
mysqli_stmt_execute($insertMsgStmt);
$messageId = mysqli_insert_id($conn);

$updateStmt = prepareOrFail($conn, "UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?");
mysqli_stmt_bind_param($updateStmt, 'i', $conversationId);
mysqli_stmt_execute($updateStmt);

echo json_encode([
  'conversation_id' => $conversationId,
  'message_id'       => $messageId,
  'attachment_path'  => $attachmentPath,
  'sent_at'          => date('c'),
]);
