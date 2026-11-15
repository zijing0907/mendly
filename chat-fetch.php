<?php
require_once 'includes/db.php';
/** @var mysqli $conn */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
header('Content-Type: application/json');

// process-login.php sets $_SESSION['user_id'] and $_SESSION['role'] ('customer' | 'expert').
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not logged in']);
  exit;
}

$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'] === 'expert' ? 'expert' : 'customer';

$action = $_GET['action'] ?? 'list';

// ---- List all conversations for the logged-in user, with a preview ----
if ($action === 'list') {
  if ($role === 'customer') {
    $sql = "SELECT c.conversation_id, e.expert_id AS contact_id, e.full_name AS contact_name,
                       (SELECT message_text FROM messages m
                          WHERE m.conversation_id = c.conversation_id
                          ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                       (SELECT COUNT(*) FROM messages m
                          WHERE m.conversation_id = c.conversation_id
                            AND m.sender_type = 'expert' AND m.is_read = 0) AS unread_count
                FROM conversations c
                JOIN experts e ON e.expert_id = c.expert_id
                WHERE c.customer_id = ?
                ORDER BY c.last_message_at DESC";
  } else {
    $sql = "SELECT c.conversation_id, cu.customer_id AS contact_id, cu.full_name AS contact_name,
                       (SELECT message_text FROM messages m
                          WHERE m.conversation_id = c.conversation_id
                          ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                       (SELECT COUNT(*) FROM messages m
                          WHERE m.conversation_id = c.conversation_id
                            AND m.sender_type = 'customer' AND m.is_read = 0) AS unread_count
                FROM conversations c
                JOIN customers cu ON cu.customer_id = c.customer_id
                WHERE c.expert_id = ?
                ORDER BY c.last_message_at DESC";
  }

  $stmt = prepareOrFail($conn, $sql);
  mysqli_stmt_bind_param($stmt, 'i', $userId);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
  echo json_encode($rows);
  exit;
}

// ---- Fetch full message history for one conversation, and mark it read ----
if ($action === 'messages') {
  $conversationId = (int) ($_GET['conversation_id'] ?? 0);
  if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id required']);
    exit;
  }

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

  $msgStmt = prepareOrFail($conn, "SELECT message_id, sender_type, sender_id, message_text, attachment_path, sent_at, is_read
                                     FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC");
  mysqli_stmt_bind_param($msgStmt, 'i', $conversationId);
  mysqli_stmt_execute($msgStmt);
  $msgResult = mysqli_stmt_get_result($msgStmt);
  $messages = mysqli_fetch_all($msgResult, MYSQLI_ASSOC);

  $otherType = $role === 'customer' ? 'expert' : 'customer';
  $readStmt = prepareOrFail($conn, "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = ?");
  mysqli_stmt_bind_param($readStmt, 'is', $conversationId, $otherType);
  mysqli_stmt_execute($readStmt);

  echo json_encode($messages);
  exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
