<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
// Lets a customer withdraw one of their own repair requests while it is
// still Pending. Once the expert has accepted it, cancelling has to go
// through the expert (via chat), so later statuses are refused.
require_once __DIR__ . '/../includes/db.php';

$customerId = requireRole('customer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('pages/my-requests.php'));
    exit;
}

$requestId = (int) ($_POST['request_id'] ?? 0);

// Load the request only if it belongs to this customer
$stmt = mysqli_prepare(
    $conn,
    "SELECT rq.status, rq.item_name, s.expert_id
     FROM repair_requests rq
     INNER JOIN services s ON rq.service_id = s.service_id
     WHERE rq.request_id = ? AND rq.customer_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $requestId, $customerId);
mysqli_stmt_execute($stmt);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$request || $request['status'] !== 'Pending') {
    header('Location: ' . url('pages/my-requests.php?cancel_error=1'));
    exit;
}

// The status condition is repeated in the UPDATE so a request the expert
// accepts at the same moment can't be cancelled by mistake.
$update = mysqli_prepare(
    $conn,
    "UPDATE repair_requests SET status = 'Cancelled'
     WHERE request_id = ? AND customer_id = ? AND status = 'Pending'"
);
mysqli_stmt_bind_param($update, 'ii', $requestId, $customerId);
mysqli_stmt_execute($update);

if (mysqli_stmt_affected_rows($update) === 1) {
    sendAutoMessage(
        $conn,
        $customerId,
        (int) $request['expert_id'],
        "❌ I've cancelled my repair request for {$request['item_name']}.",
        'customer'
    );
    header('Location: ' . url('pages/my-requests.php?cancelled=1'));
} else {
    header('Location: ' . url('pages/my-requests.php?cancel_error=1'));
}
exit;
