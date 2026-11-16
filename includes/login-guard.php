<?php
// Login protection: slows down password guessing ("brute force").
//
// Every attempt is recorded in login_attempts. Too many failures in a
// short time, for one account or from one IP address, and that account or
// address has to wait before trying again:
//
//   login (customers/experts)  5 wrong passwords per email, 25 per IP, in 15 minutes
//   admin login                5 per username, 15 per IP, in 15 minutes
//   password reset requests    3 per email, 10 per IP, in 15 minutes (every request counts)
//
// Attempts are counted even for emails that don't exist, so the limits
// behave the same either way and can't be used to find real accounts.
// All times use MySQL's clock (NOW()) to avoid PHP/MySQL timezone mismatches.

require_once __DIR__ . '/db.php';

const LOGIN_GUARD_RULES = [
    'login' => ['per_identifier' => 5, 'per_ip' => 25, 'minutes' => 15, 'failures_only' => true],
    'admin' => ['per_identifier' => 5, 'per_ip' => 15, 'minutes' => 15, 'failures_only' => true],
    'reset' => ['per_identifier' => 3, 'per_ip' => 10, 'minutes' => 15, 'failures_only' => false],
];

function guardClientIp(): string
{
    // REMOTE_ADDR only: headers like X-Forwarded-For can be faked by anyone
    return substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
}

function guardIdentifier(string $identifier): string
{
    return mb_substr(mb_strtolower(trim($identifier)), 0, 190);
}

// How many minutes until this email/username (or this IP) may try again.
// 0 means go ahead.
function loginLockedMinutes(string $scope, string $identifier): int
{
    $rule    = LOGIN_GUARD_RULES[$scope];
    $conn    = $GLOBALS['conn'];
    $minutes = (int) $rule['minutes'];
    $onlyFailures = $rule['failures_only'] ? 'AND success = FALSE' : '';

    $checks = [
        ['identifier', guardIdentifier($identifier), (int) $rule['per_identifier']],
        ['ip_address', guardClientIp(),              (int) $rule['per_ip']],
    ];

    $wait = 0;
    foreach ($checks as [$column, $value, $limit]) {
        // Look at the most recent $limit attempts inside the window. If there
        // are $limit of them, the lock lasts until the oldest one is
        // $minutes old (a sliding window).
        $stmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) AS attempts,
                    TIMESTAMPDIFF(SECOND, NOW(), MIN(attempted_at) + INTERVAL $minutes MINUTE) AS seconds_left
             FROM (
                 SELECT attempted_at FROM login_attempts
                 WHERE scope = ? AND $column = ? $onlyFailures
                   AND attempted_at > NOW() - INTERVAL $minutes MINUTE
                 ORDER BY attempted_at DESC
                 LIMIT $limit
             ) recent"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $scope, $value);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ((int) $row['attempts'] >= $limit) {
            $wait = max($wait, (int) ceil(max(1, (int) $row['seconds_left']) / 60));
        }
    }
    return $wait;
}

// Record one attempt. A successful login wipes that account's earlier
// failures, so a user who mistyped a few times starts fresh.
function recordLoginAttempt(string $scope, string $identifier, bool $success): void
{
    $conn       = $GLOBALS['conn'];
    $identifier = guardIdentifier($identifier);
    $ip         = guardClientIp();
    $successInt = $success ? 1 : 0;

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO login_attempts (scope, identifier, ip_address, success) VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'sssi', $scope, $identifier, $ip, $successInt);
    mysqli_stmt_execute($stmt);

    if ($success && LOGIN_GUARD_RULES[$scope]['failures_only']) {
        $clear = mysqli_prepare(
            $conn,
            "DELETE FROM login_attempts WHERE scope = ? AND identifier = ? AND success = FALSE"
        );
        mysqli_stmt_bind_param($clear, 'ss', $scope, $identifier);
        mysqli_stmt_execute($clear);
    }

    // Now and then, throw away records older than a day
    if (random_int(1, 50) === 1) {
        mysqli_query($conn, "DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY");
    }
}
