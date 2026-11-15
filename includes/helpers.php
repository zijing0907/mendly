<?php
// Small helpers shared across pages. Plain PHP only — the assignment
// rules out frameworks, so these stay deliberately tiny.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/icons.php';   // icon(), nameInitials()

// ---------------------------------------------------------------------
// URLs
// ---------------------------------------------------------------------
// Pages live in pages/, form handlers in actions/ and JSON endpoints in
// api/, so links can't just be "login.php" any more. basePath() works out
// the web path of the project root by comparing the running script's URL
// (SCRIPT_NAME) with its location on disk. That gives "/mendly" under
// XAMPP (htdocs/mendly) and "" with `php -S`, without any configuration.
function basePath(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $root   = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
    $script = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $name   = $_SERVER['SCRIPT_NAME'] ?? '';

    $base = '';
    if ($root !== '' && strncasecmp($script, $root, strlen($root)) === 0) {
        $relative = substr($script, strlen($root));            // e.g. "/pages/login.php"
        if ($relative !== '' && substr($name, -strlen($relative)) === $relative) {
            $base = substr($name, 0, -strlen($relative));      // e.g. "/mendly"
        }
    }
    return $base;
}

// Absolute web path for something in the project, e.g. url('pages/login.php').
// Every page also prints a <base href> tag pointing at url(), so plain
// relative links in HTML and JavaScript resolve from the project root too.
function url(string $path = ''): string
{
    return basePath() . '/' . ltrim($path, '/');
}

// Redirect to a project path and stop.
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

// Escape a value for HTML output. Accepts NULL (common for optional DB
// columns) so pages don't trigger "passing null to htmlspecialchars"
// deprecation warnings on PHP 8.1+.
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Profile photos are stored in the database as raw bytes (MEDIUMBLOB)
// plus a MIME type. Turn that pair into a data: URI usable as <img src>,
// or return $fallback when no photo has been uploaded.
function imageDataUri(?string $data, ?string $mime, ?string $fallback = null): ?string
{
    if ($data === null || $data === '' || $mime === null || $mime === '') {
        return $fallback;
    }
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

// Role of whoever is logged in: 'customer', 'expert', or 'guest'.
function currentRole(): string
{
    return isset($_SESSION['user_id']) ? ($_SESSION['role'] ?? 'guest') : 'guest';
}

// Gatekeeper for pages that need a specific kind of account. Sends guests
// to the login page and the wrong role to the home page, otherwise returns
// the logged-in user's ID.
function requireRole(string $role): int
{
    if (!isset($_SESSION['user_id'])) {
        redirect('pages/login.php');
    }
    if (currentRole() !== $role) {
        redirect('index.php');
    }
    return (int) $_SESSION['user_id'];
}

// Map a detected image MIME type to a safe file extension. Uploads are
// always saved with an extension we choose, never the one the browser
// sent, so a file named "shell.php" can't end up executable on the server.
function safeImageExtension(string $tmpPath): ?string
{
    $info = @getimagesize($tmpPath);
    if ($info === false) {
        return null;
    }
    $map = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    return $map[$info[2]] ?? null;
}

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------
// Without this, another website could make a logged-in visitor's browser
// submit a form to Mendly (change their email, delete a service...) and the
// browser would helpfully attach their session cookie.
//
// Each session gets a random secret. Our pages put it in every form
// (csrfField) and in a <meta> tag that JavaScript sends back as an
// X-CSRF-Token header. Another site can trigger the request but can't read
// the secret, so the check at the bottom of this file rejects it.
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

// php.ini sizes like "8M" or "512K" in bytes (0 = no limit)
function iniBytes($value): int
{
    $value = trim((string) $value);
    $bytes = (int) $value;
    switch (strtoupper(substr($value, -1))) {
        case 'G': $bytes *= 1024;
        // no break
        case 'M': $bytes *= 1024;
        // no break
        case 'K': $bytes *= 1024;
    }
    return $bytes > 0 ? $bytes : PHP_INT_MAX;
}

// Stop a POST request that doesn't carry this session's token.
function verifyCsrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $isApi = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;

    // When an upload is bigger than PHP's post_max_size, PHP throws the whole
    // body away, token included. Say what actually went wrong. (JSON bodies
    // also leave $_POST empty, so compare against the real limit.)
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > iniBytes(ini_get('post_max_size'))) {
        $message = 'That upload is too large. Please choose smaller files.';
    } else {
        $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (is_string($sent) && $sent !== '' && hash_equals(csrfToken(), $sent)) {
            return;
        }
        $message = 'Your session has expired. Please go back, refresh the page and try again.';
    }

    http_response_code(403);
    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    } else {
        echo '<!DOCTYPE html><meta charset="utf-8"><title>Request blocked</title>'
            . '<p style="font-family:sans-serif;margin:40px">' . e($message)
            . ' <a href="' . e(url('index.php')) . '">Back to Mendly</a></p>';
    }
    exit;
}

// ---------------------------------------------------------------------
// Runs on every request (every page, action and API file loads this first)
// ---------------------------------------------------------------------

// "Remember me": the session has ended but the browser still has a
// remember cookie, so log the user back in (see includes/auth.php).
if (!isset($_SESSION['user_id']) && isset($_COOKIE['mendly_remember'])) {
    require_once __DIR__ . '/auth.php';
    restoreRememberedLogin();
}

// Reject POSTs without this session's CSRF token. New forms and endpoints
// are protected automatically.
verifyCsrf();
