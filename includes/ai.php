<?php
// Optional AI features, powered by Google's Gemini API (the free tier works).
//
//   - photoRejectionReason(): checks an uploaded photo for gore/violence or
//     sexual content before it's saved
//   - the help assistant (api/assistant.php) uses geminiGenerate() too
//
// Turn it on by putting GEMINI_API_KEY in .env (free key from
// https://aistudio.google.com/apikey). With no key, or if Gemini is down or
// over its limit, the site carries on without AI: photos are accepted and
// the assistant answers from built-in FAQ/search instead.
//
// Note: on Gemini's free tier, Google may use what you send to improve its
// products. Fine for a demo with sample data, not for real customers.

require_once __DIR__ . '/db.php';   // loads .env, gives $conn

function aiConfigured(): bool
{
    return (getenv('GEMINI_API_KEY') ?: '') !== '';
}

function aiModel(): string
{
    return getenv('GEMINI_MODEL') ?: 'gemini-3.5-flash-lite';
}

// Call Gemini's generateContent endpoint.
// Returns ['ok' => bool, 'text' => string, 'blocked' => bool, 'status' => int, 'error' => string].
// 'blocked' means Gemini's own safety filters refused the request.
function geminiGenerate(array $body, int $timeoutSeconds = 20): array
{
    $result = ['ok' => false, 'text' => '', 'blocked' => false, 'status' => 0, 'error' => ''];

    // GEMINI_BASE_URL only exists so tests can point at a fake server
    $base = rtrim(getenv('GEMINI_BASE_URL') ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
    $url  = $base . '/models/' . rawurlencode(aiModel()) . ':generateContent';

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . getenv('GEMINI_API_KEY'),   // never put the key in the URL
        ],
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => $timeoutSeconds,
    ]);
    $raw              = curl_exec($curl);
    $result['status'] = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    if ($raw === false) {
        $result['error'] = 'network error: ' . curl_error($curl);
        error_log('Mendly AI: ' . $result['error']);
        return $result;
    }

    $data = json_decode($raw, true);

    if ($result['status'] !== 200) {
        // e.g. 429 RESOURCE_EXHAUSTED (free-tier limit), 400 API_KEY_INVALID,
        // 503 UNAVAILABLE (overloaded)
        $result['error'] = 'HTTP ' . $result['status'] . ' ' . ($data['error']['status'] ?? '')
            . ': ' . mb_substr((string) ($data['error']['message'] ?? $raw), 0, 200);
        error_log('Mendly AI: ' . $result['error']);
        return $result;
    }

    // The request itself was blocked by Gemini's safety filters
    if (!empty($data['promptFeedback']['blockReason'])) {
        $result['blocked'] = true;
        $result['error']   = 'blocked: ' . $data['promptFeedback']['blockReason'];
        return $result;
    }

    $candidate = $data['candidates'][0] ?? null;
    $finish    = $candidate['finishReason'] ?? '';
    if (in_array($finish, ['SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST', 'SPII', 'IMAGE_SAFETY'], true)) {
        $result['blocked'] = true;
        $result['error']   = 'blocked: ' . $finish;
        return $result;
    }

    // Join the answer's text parts (skipping any "thought" summaries)
    foreach ($candidate['content']['parts'] ?? [] as $part) {
        if (isset($part['text']) && empty($part['thought'])) {
            $result['text'] .= $part['text'];
        }
    }

    if ($result['text'] === '') {
        $result['error'] = 'empty response (finishReason ' . ($finish ?: 'none') . ')';
        error_log('Mendly AI: ' . $result['error']);
        return $result;
    }

    $result['ok'] = true;
    return $result;
}

// Count one use of an AI feature for today. Returns false once today's
// limit is reached, so a public demo can't burn through the free quota.
function aiTakeDailyQuota(string $feature, int $dailyLimit): bool
{
    $conn = $GLOBALS['conn'];
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO ai_usage (usage_date, feature, uses) VALUES (CURDATE(), ?, 1)
         ON DUPLICATE KEY UPDATE uses = uses + 1"
    );
    mysqli_stmt_bind_param($stmt, 's', $feature);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conn, "SELECT uses FROM ai_usage WHERE usage_date = CURDATE() AND feature = ?");
    mysqli_stmt_bind_param($stmt, 's', $feature);
    mysqli_stmt_execute($stmt);
    $uses = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['uses'] ?? 0);

    return $uses <= $dailyLimit;
}

// ---------------------------------------------------------------------
// Photo safety check
// ---------------------------------------------------------------------

// Read an uploaded image and prepare it for Gemini: shrink anything big to
// at most 1024px (fewer tokens, faster upload) and convert GIFs to JPEG,
// which Gemini doesn't accept. Returns ['mime' => ..., 'data' => base64] or
// null if the file can't be prepared.
function aiImagePart(string $path): ?array
{
    $info = @getimagesize($path);
    if ($info === false) {
        return null;
    }
    $mime = $info['mime'];

    $image = function_exists('imagecreatefromstring') ? @imagecreatefromstring((string) file_get_contents($path)) : false;

    if ($image === false) {
        // GD can't read it (e.g. WebP on some PHP builds): send it as-is if
        // Gemini supports the format
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }
        return ['mime' => $mime, 'data' => base64_encode((string) file_get_contents($path))];
    }

    $width  = imagesx($image);
    $height = imagesy($image);
    $scale  = min(1, 1024 / max($width, $height));
    $newW   = max(1, (int) round($width * $scale));
    $newH   = max(1, (int) round($height * $scale));

    // White background so transparent PNGs don't turn black
    $canvas = imagecreatetruecolor($newW, $newH);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);

    ob_start();
    imagejpeg($canvas, null, 82);
    $jpeg = (string) ob_get_clean();

    return ['mime' => 'image/jpeg', 'data' => base64_encode($jpeg)];
}

const PHOTO_CHECK_PROMPT = <<<PROMPT
You review photos uploaded to Mendly, a marketplace where people send damaged
belongings (bags, shoes, clothes, books, toys, documents) to repair experts.
The photo will be shown publicly or to another user.

Reject the photo if it shows any of:
- gore_violence: blood or wounds on people or animals, injury, graphic violence, dead bodies, cruelty
- sexual_nudity: nudity, sexual content or sexually suggestive poses
- other_disturbing: self-harm, hate symbols, or other shocking content

Allow everything else: damaged or dirty items, stains (including red paint or
dye), tools, workshops, people in normal clothing, pets, screenshots, text.
When unsure whether a red mark is blood on a person, look for a body or wound.
PROMPT;

// Returns null if the photo is fine (or couldn't be checked), otherwise a
// short message to show the uploader.
function photoRejectionReason(string $path): ?string
{
    if (!aiConfigured()) {
        return null;   // AI off: no check
    }

    $image = aiImagePart($path);
    if ($image === null) {
        return null;
    }

    $response = geminiGenerate([
        'system_instruction' => ['parts' => [['text' => PHOTO_CHECK_PROMPT]]],
        'contents' => [[
            'role'  => 'user',
            'parts' => [
                ['text' => 'Check this uploaded photo.'],
                ['inline_data' => ['mime_type' => $image['mime'], 'data' => $image['data']]],
            ],
        ]],
        'generationConfig' => [
            'maxOutputTokens'    => 300,
            'thinkingConfig'     => ['thinkingLevel' => 'minimal'],   // a quick classification
            'responseMimeType'   => 'application/json',
            'responseJsonSchema' => [
                'type'       => 'object',
                'properties' => [
                    'allowed'  => ['type' => 'boolean'],
                    'category' => ['type' => 'string', 'enum' => ['ok', 'gore_violence', 'sexual_nudity', 'other_disturbing']],
                    'reason'   => ['type' => 'string', 'description' => 'One short sentence'],
                ],
                'required'   => ['allowed', 'category', 'reason'],
            ],
        ],
    ]);

    $messages = [
        'gore_violence'    => 'That photo was blocked by our safety check because it appears to show blood, injury or violence. Please choose a different photo.',
        'sexual_nudity'    => 'That photo was blocked by our safety check because it appears to contain nudity or sexual content. Please choose a different photo.',
        'other_disturbing' => 'That photo was blocked by our safety check because it appears to contain disturbing content. Please choose a different photo.',
    ];

    // Gemini's own filters refused to even look at it: treat as unsafe
    if ($response['blocked']) {
        return $messages['other_disturbing'];
    }

    // Network error, over the free limit, etc. We let the photo through
    // rather than block every upload while Gemini is unavailable (logged).
    if (!$response['ok']) {
        return null;
    }

    $verdict = json_decode($response['text'], true);
    if (!is_array($verdict) || !isset($verdict['allowed'])) {
        error_log('Mendly AI: unexpected photo check reply: ' . mb_substr($response['text'], 0, 200));
        return null;
    }

    if ($verdict['allowed'] === true) {
        return null;
    }
    return $messages[$verdict['category'] ?? ''] ?? $messages['other_disturbing'];
}
