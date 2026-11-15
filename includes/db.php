<?php
// Database configuration
// Update these if your local MySQL setup is different (e.g. XAMPP default
// is host 'localhost', user 'root', empty password, port 3306).
$dbHost     = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName     = 'mendly_db';
$dbPort     = 3308; // confirm this matches your actual MySQL port

// On PHP 8.1+, mysqli throws an exception on connection failure by
// default, which skips the friendly die() message below entirely and
// shows a raw stack trace instead. This restores the old behaviour
// (mysqli_connect() just returns false on failure, like it always did),
// so our own error handling actually runs.
mysqli_report(MYSQLI_REPORT_OFF);

// On PHP 8.1+, mysqli throws an exception on connection/query failure by
// default, which skips any of our own error handling entirely and shows
// a raw stack trace instead. This restores the old behaviour (mysqli
// functions just return false on failure, like they always did), so our
// own error handling actually runs.
mysqli_report(MYSQLI_REPORT_OFF);

// Create a database connection
$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName, $dbPort);

// Check if the connection was successful
if (!$conn) {
    die(
        'Could not connect to the database: ' . mysqli_connect_error() . '. '
        . 'If you are a teammate setting this up locally, check that '
        . '$dbUsername/$dbPassword in includes/db.php match your own '
        . 'MySQL root credentials, and that the mendly_db database has '
        . 'been imported.'
    );
}

// mysqli_prepare() returns false (not a mysqli_stmt) if the SQL fails to
// prepare, e.g. a typo in a column/table name. Routing every prepare
// through this helper means that failure surfaces as a clean JSON error
// instead of a fatal "call to a member function on false", and it also
// satisfies editors that flag $stmt as possibly-false.
function prepareOrFail(mysqli $conn, string $sql): mysqli_stmt
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed to prepare: ' . mysqli_error($conn)]);
        exit;
    }
    return $stmt;
}

// Drops an automated/system message into the customer<->expert
// conversation tied to a repair request, creating the conversation if this
// pair doesn't have one yet. Reuses the same "ON DUPLICATE KEY" pattern as
// chat-send.php's new-conversation branch, so this never creates a
// duplicate row alongside a conversation the two of them already have from
// browsing/messaging separately.
//
// Automated messages are sent as sender_type='expert' since they're
// notifications about work the expert is doing ("your request is
// pending", "we've started repairing it", etc). If you'd rather these
// come from a dedicated "system" sender, you'd need to widen the
// messages.sender_type ENUM first.
function sendAutoMessage(mysqli $conn, int $customerId, int $expertId, string $text): int
{
    $convoStmt = prepareOrFail(
        $conn,
        "INSERT INTO conversations (customer_id, expert_id) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE conversation_id = LAST_INSERT_ID(conversation_id)"
    );
    mysqli_stmt_bind_param($convoStmt, 'ii', $customerId, $expertId);
    mysqli_stmt_execute($convoStmt);
    $conversationId = mysqli_insert_id($conn);

    $msgStmt = prepareOrFail(
        $conn,
        "INSERT INTO messages (conversation_id, sender_type, sender_id, message_text)
         VALUES (?, 'expert', ?, ?)"
    );
    mysqli_stmt_bind_param($msgStmt, 'iis', $conversationId, $expertId, $text);
    mysqli_stmt_execute($msgStmt);

    $updateStmt = prepareOrFail(
        $conn,
        "UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?"
    );
    mysqli_stmt_bind_param($updateStmt, 'i', $conversationId);
    mysqli_stmt_execute($updateStmt);

    return $conversationId;
}
