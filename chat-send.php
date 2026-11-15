<?php
require_once 'includes/db.php';
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not logged in']);
  exit;
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] === 'expert' ? 'expert' : 'customer';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$text           = trim($input['text'] ?? '');
$conversationId = (int) ($input['conversation_id'] ?? 0);
$targetId       = (int) ($input['target_id'] ?? 0); // only needed when starting a brand-new conversation

if ($text === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Message text required']);
  exit;
}

if ($conversationId) {
  // Sending into an existing conversation — confirm the user actually owns it.
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
} else {
  // Starting a new conversation (e.g. "message this expert" from a service page).
  if (!$targetId) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id or target_id required']);
    exit;
  }
  $customerId = $role === 'customer' ? $userId : $targetId;
  $expertId   = $role === 'expert'   ? $userId : $targetId;

  // Make sure the other person actually exists before opening a thread
  $otherTable = $role === 'customer' ? 'experts' : 'customers';
  $otherCol   = $role === 'customer' ? 'expert_id' : 'customer_id';
  $targetStmt = prepareOrFail($conn, "SELECT $otherCol FROM $otherTable WHERE $otherCol = ?");
  mysqli_stmt_bind_param($targetStmt, 'i', $targetId);
  mysqli_stmt_execute($targetStmt);
  if (!mysqli_fetch_assoc(mysqli_stmt_get_result($targetStmt))) {
    http_response_code(404);
    echo json_encode(['error' => 'That account no longer exists']);
    exit;
  }

  // Reuses the existing conversation if this pair already has one.
  $insertConvoStmt = prepareOrFail($conn, "INSERT INTO conversations (customer_id, expert_id) VALUES (?, ?)
                                               ON DUPLICATE KEY UPDATE conversation_id = LAST_INSERT_ID(conversation_id)");
  mysqli_stmt_bind_param($insertConvoStmt, 'ii', $customerId, $expertId);
  mysqli_stmt_execute($insertConvoStmt);
  $conversationId = mysqli_insert_id($conn);
}

$insertMsgStmt = prepareOrFail($conn, "INSERT INTO messages (conversation_id, sender_type, sender_id, message_text)
                                         VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($insertMsgStmt, 'isis', $conversationId, $role, $userId, $text);
mysqli_stmt_execute($insertMsgStmt);
$messageId = mysqli_insert_id($conn);

$updateStmt = prepareOrFail($conn, "UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?");
mysqli_stmt_bind_param($updateStmt, 'i', $conversationId);
mysqli_stmt_execute($updateStmt);

echo json_encode([
  'conversation_id' => $conversationId,
  'message_id'      => $messageId,
  'sent_at'         => date('c'),
]);
