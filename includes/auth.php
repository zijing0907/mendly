<?php
// Logging in, and "Remember me".
//
// Remember me uses the selector/validator pattern:
//   - the cookie holds "selector:validator", two random values
//   - the database stores the selector and a SHA-256 hash of the validator
// The selector finds the row; the validator proves the cookie is genuine.
// Because only a hash is stored, a leaked remember_tokens table can't be
// turned into working cookies. Every time a cookie is used it's replaced
// with a fresh one, and logout / password changes delete the rows.

require_once __DIR__ . '/helpers.php';

const REMEMBER_COOKIE = 'mendly_remember';
const REMEMBER_DAYS   = 30;

// Put a customer or expert into the session. $account needs id, full_name,
// avatar and avatar_mime.
function loginUser(string $role, array $account): void
{
    // New session ID on login, so a session ID planted beforehand
    // (session fixation) is worthless.
    session_regenerate_id(true);

    $_SESSION['user_id']   = (int) $account['id'];
    $_SESSION['role']      = $role;
    $_SESSION['full_name'] = $account['full_name'];
    $_SESSION['avatar']    = imageDataUri($account['avatar'] ?? null, $account['avatar_mime'] ?? null);
}

function setRememberCookie(string $value, int $expires): void
{
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => basePath() . '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,      // JavaScript can't read it
        'samesite' => 'Lax',
    ]);
}

// Issue a Remember-me cookie for this browser.
function rememberLogin(mysqli $conn, string $role, int $userId): void
{
    $selector  = bin2hex(random_bytes(12));   // 24 hex characters
    $validator = bin2hex(random_bytes(32));   // 64 hex characters
    $hash      = hash('sha256', $validator);
    $days      = REMEMBER_DAYS;

    mysqli_query($conn, "DELETE FROM remember_tokens WHERE expires_at < NOW()");

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO remember_tokens (user_type, user_id, selector, token_hash, expires_at)
         VALUES (?, ?, ?, ?, NOW() + INTERVAL $days DAY)"
    );
    mysqli_stmt_bind_param($stmt, 'siss', $role, $userId, $selector, $hash);
    mysqli_stmt_execute($stmt);

    setRememberCookie($selector . ':' . $validator, time() + REMEMBER_DAYS * 86400);
}

// Split the cookie into [selector, validator], or null if it's malformed.
function readRememberCookie(): ?array
{
    $parts = explode(':', (string) ($_COOKIE[REMEMBER_COOKIE] ?? ''), 2);
    if (count($parts) !== 2
        || strlen($parts[0]) !== 24 || !ctype_xdigit($parts[0])
        || strlen($parts[1]) !== 64 || !ctype_xdigit($parts[1])) {
        return null;
    }
    return $parts;
}

// Log out this browser: delete its token and the cookie.
function forgetLogin(mysqli $conn): void
{
    $parts = readRememberCookie();
    if ($parts) {
        $stmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE selector = ?");
        mysqli_stmt_bind_param($stmt, 's', $parts[0]);
        mysqli_stmt_execute($stmt);
    }
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        setRememberCookie('', time() - 3600);
    }
}

// Log the user out everywhere: used after a password change/reset or
// account deletion.
function forgetAllLogins(mysqli $conn, string $role, int $userId): void
{
    $stmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE user_type = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $role, $userId);
    mysqli_stmt_execute($stmt);
}

// Called by helpers.php when there's no logged-in session but the browser
// has a Remember-me cookie.
function restoreRememberedLogin(): void
{
    $parts = readRememberCookie();
    if (!$parts) {
        setRememberCookie('', time() - 3600);
        return;
    }
    [$selector, $validator] = $parts;

    require_once __DIR__ . '/db.php';
    $conn = $GLOBALS['conn'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT token_id, user_type, user_id, token_hash FROM remember_tokens
         WHERE selector = ? AND expires_at > NOW()"
    );
    mysqli_stmt_bind_param($stmt, 's', $selector);
    mysqli_stmt_execute($stmt);
    $token = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$token || !hash_equals($token['token_hash'], hash('sha256', $validator))) {
        forgetLogin($conn);
        return;
    }

    $role   = $token['user_type'] === 'expert' ? 'expert' : 'customer';
    $table  = $role === 'expert' ? 'experts' : 'customers';
    $idCol  = $role === 'expert' ? 'expert_id' : 'customer_id';
    $userId = (int) $token['user_id'];

    $accountStmt = mysqli_prepare($conn, "SELECT $idCol AS id, full_name, avatar, avatar_mime FROM $table WHERE $idCol = ?");
    mysqli_stmt_bind_param($accountStmt, 'i', $userId);
    mysqli_stmt_execute($accountStmt);
    $account = mysqli_fetch_assoc(mysqli_stmt_get_result($accountStmt));

    // Each token works once: delete it and hand out a fresh one
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE token_id = ?");
    mysqli_stmt_bind_param($deleteStmt, 'i', $token['token_id']);
    mysqli_stmt_execute($deleteStmt);

    if (!$account) {
        setRememberCookie('', time() - 3600);
        return;
    }

    loginUser($role, $account);
    rememberLogin($conn, $role, $userId);
}
