<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/assistant.php';

// JSON endpoint for the "Ask Mendly" widget (assets/js/assistant.js).
// Open to everyone, including guests. The CSRF check in helpers.php still
// applies, and each visitor gets a limited number of questions per hour.
//
// Request:  {"messages": [{"role": "user"|"model", "text": "..."}, ...]}
// Response: {"reply": "...", "source": "ai"|"faq"}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

const MAX_TURNS        = 10;    // conversation history sent along
const MAX_CHARS        = 500;   // per message
const QUESTIONS_HOURLY = 20;    // per visitor (session)

$input    = json_decode(file_get_contents('php://input'), true);
$messages = is_array($input['messages'] ?? null) ? array_slice($input['messages'], -MAX_TURNS) : [];

$history = [];
foreach ($messages as $message) {
    $role = $message['role'] ?? '';
    $text = trim((string) ($message['text'] ?? ''));
    if (!in_array($role, ['user', 'model'], true) || $text === '') {
        continue;
    }
    $history[] = ['role' => $role, 'text' => mb_substr($text, 0, MAX_CHARS)];
}

// Gemini expects the conversation to start with the user and end with them
while ($history && $history[0]['role'] !== 'user') {
    array_shift($history);
}
if (!$history || end($history)['role'] !== 'user') {
    http_response_code(400);
    echo json_encode(['error' => 'Please type a question.']);
    exit;
}

// Simple per-visitor rate limit, kept in the session
$now    = time();
$recent = array_filter($_SESSION['assistant_times'] ?? [], fn($t) => $t > $now - 3600);
if (count($recent) >= QUESTIONS_HOURLY) {
    http_response_code(429);
    echo json_encode(['reply' => "You've asked a lot of questions in the last hour. Please try again a bit later, or browse the services directly.", 'source' => 'limit']);
    exit;
}
$recent[] = $now;
$_SESSION['assistant_times'] = array_values($recent);

echo json_encode(assistantReply($history));
