<?php
// Small helpers shared across pages. Plain PHP only — the assignment
// rules out frameworks, so these stay deliberately tiny.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
