<?php
require_once __DIR__ . '/helpers.php';

// ---------------------------------------------------------------------
// Database configuration
// ---------------------------------------------------------------------
// Settings are read from environment variables, or from a .env file in
// the project root (copy .env.example to .env). Nothing secret lives in
// source control. The defaults match a stock XAMPP install: localhost,
// port 3306, user root, empty password.
function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        // Real environment variables win over the .env file
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

loadEnvFile(dirname(__DIR__) . '/.env');

$dbHost     = getenv('DB_HOST') ?: 'localhost';
$dbPort     = (int) (getenv('DB_PORT') ?: 3306);
$dbUsername = getenv('DB_USERNAME') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$dbName     = getenv('DB_NAME') ?: 'mendly_db';

// On PHP 8.1+, mysqli throws an exception on connection/query failure by
// default, which skips our own error handling and shows a raw stack
// trace. This restores the classic behaviour (functions return false) so
// the checks below and in each page actually run.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName, $dbPort);

if (!$conn) {
    http_response_code(500);
    die(
        'Could not connect to the database: ' . e(mysqli_connect_error()) . '. '
        . 'Check the DB_* settings in your .env file (see .env.example) and '
        . 'that database/mendly-schema.sql has been imported.'
    );
}

// utf8mb4 so emoji in chat messages and reviews survive the round trip
mysqli_set_charset($conn, 'utf8mb4');

// Make the connection global even when this file is first loaded from
// inside a function (the "Remember me" check in auth.php does that).
$GLOBALS['conn'] = $conn;

// mysqli_prepare() returns false (not a mysqli_stmt) if the SQL fails to
// prepare, e.g. a typo in a column/table name. Routing every prepare
// through this helper means that failure surfaces as a clean JSON error
// instead of a fatal "call to a member function on false".
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
// pair doesn't have one yet. Uses the same "ON DUPLICATE KEY" pattern as
// api/chat-send.php, so it never creates a duplicate conversation row.
//
// Automated messages are sent as sender_type='expert' by default since
// they're mostly notifications about work the expert is doing ("your
// request is pending", "we've started repairing it", etc). Pass
// 'customer' for notices triggered by the customer, like a cancellation.
function sendAutoMessage(mysqli $conn, int $customerId, int $expertId, string $text, string $senderType = 'expert'): int
{
    $senderType = $senderType === 'customer' ? 'customer' : 'expert';
    $senderId   = $senderType === 'customer' ? $customerId : $expertId;

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
         VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($msgStmt, 'isis', $conversationId, $senderType, $senderId, $text);
    mysqli_stmt_execute($msgStmt);

    $updateStmt = prepareOrFail(
        $conn,
        "UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?"
    );
    mysqli_stmt_bind_param($updateStmt, 'i', $conversationId);
    mysqli_stmt_execute($updateStmt);

    return $conversationId;
}
