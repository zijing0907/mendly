<?php
// Called by the expert's request-management UI when they move a repair
// request forward (Pending -> Accepted -> In Progress -> Ready for
// Collection -> Completed, or Cancelled). Updates repair_requests.status
// and drops a matching automated message into the chat with the customer.

session_start();
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'expert') {
  http_response_code(401);
  echo json_encode(['error' => 'Not logged in as an expert']);
  exit;
}

$expertId  = (int) $_SESSION['user_id'];
$requestId = (int) ($_POST['request_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';

$allowed = ['Accepted', 'In Progress', 'Ready for Collection', 'Completed', 'Cancelled'];
if (!$requestId || !in_array($newStatus, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid request']);
  exit;
}

// Confirm this request actually belongs to a service owned by this expert
// — otherwise any logged-in expert could push status updates (and chat
// messages) into someone else's requests just by guessing request_id.
$checkStmt = prepareOrFail($conn, "SELECT rq.customer_id, rq.item_name, rq.status
    FROM repair_requests rq
    INNER JOIN services s ON rq.service_id = s.service_id
    WHERE rq.request_id = ? AND s.expert_id = ?");
mysqli_stmt_bind_param($checkStmt, 'ii', $requestId, $expertId);
mysqli_stmt_execute($checkStmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));

if (!$row) {
  http_response_code(403);
  echo json_encode(['error' => 'Not your request']);
  exit;
}

// Same transitions my-requests-expert.php offers in its dropdown. Checking
// them here too stops a hand-crafted POST from skipping steps (e.g. jumping
// straight from Pending to Completed).
$nextSteps = [
  'Pending'              => ['Accepted', 'Cancelled'],
  'Accepted'             => ['In Progress', 'Cancelled'],
  'In Progress'          => ['Ready for Collection', 'Cancelled'],
  'Ready for Collection' => ['Completed'],
];

if (!in_array($newStatus, $nextSteps[$row['status']] ?? [], true)) {
  http_response_code(409);
  echo json_encode(['error' => "A request that is {$row['status']} can't be moved to {$newStatus}."]);
  exit;
}

$updateStmt = prepareOrFail($conn, "UPDATE repair_requests SET status = ? WHERE request_id = ?");
mysqli_stmt_bind_param($updateStmt, 'si', $newStatus, $requestId);
mysqli_stmt_execute($updateStmt);

$itemName = $row['item_name'];

$messages = [
  'Accepted'             => "✅ Your repair request for {$itemName} has been accepted. We'll begin work soon.",
  'In Progress'          => "🔧 Your repair request for {$itemName} is now In Progress — our expert has started repairing it.",
  'Ready for Collection' => "📦 Good news — your {$itemName} is repaired and ready for collection.",
  'Completed'            => "🎉 Your repair request for {$itemName} is marked Completed. Thanks for using Mendly!",
  'Cancelled'            => "❌ Your repair request for {$itemName} has been cancelled. Reach out in chat if you have any questions.",
];

sendAutoMessage($conn, (int) $row['customer_id'], $expertId, $messages[$newStatus]);

echo json_encode(['success' => true, 'status' => $newStatus]);
