<?php
// Sending email over SMTP, written from scratch (no libraries).
//
// Configure it in .env (see .env.example). With Gmail, for example:
//   MAIL_HOST=smtp.gmail.com  MAIL_PORT=587  MAIL_ENCRYPTION=tls
//   MAIL_USERNAME=you@gmail.com  MAIL_PASSWORD=<16-character app password>
// When MAIL_HOST is empty the site runs in demo mode and shows password
// reset links on screen instead of emailing them.
//
// SMTP is a plain-text conversation: the client sends a command (EHLO,
// AUTH LOGIN, MAIL FROM, RCPT TO, DATA...) and the server answers with a
// three-digit code. 2xx/3xx means carry on; anything else is an error.

require_once __DIR__ . '/db.php';   // loads .env

function mailConfigured(): bool
{
    return (getenv('MAIL_HOST') ?: '') !== '';
}

// Public address of the site, used to build links inside emails. Set
// APP_URL in .env when the site is online; otherwise links use the address
// of the current request.
function appUrl(string $path = ''): string
{
    $base = rtrim(getenv('APP_URL') ?: '', '/');
    if ($base === '') {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $base  = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . basePath();
    }
    return $base . '/' . ltrim($path, '/');
}

// Send a plain-text email. Returns false (and logs why) if anything fails.
function sendMail(string $to, string $subject, string $body): bool
{
    if (!mailConfigured() || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $host       = getenv('MAIL_HOST');
    $port       = (int) (getenv('MAIL_PORT') ?: 587);
    $encryption = strtolower(getenv('MAIL_ENCRYPTION') ?: 'tls');   // tls, ssl or none
    $username   = getenv('MAIL_USERNAME') ?: '';
    $password   = getenv('MAIL_PASSWORD') ?: '';
    $from       = getenv('MAIL_FROM') ?: $username;
    $fromName   = getenv('MAIL_FROM_NAME') ?: 'Mendly';

    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        error_log('Mendly mail: MAIL_FROM is not a valid address');
        return false;
    }

    $socket = @stream_socket_client(
        ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port,
        $errorNumber,
        $errorText,
        10
    );
    if (!$socket) {
        error_log("Mendly mail: can't connect to $host:$port ($errorText)");
        return false;
    }
    stream_set_timeout($socket, 10);

    // Send one command and check the reply code. Replies can span several
    // lines ("250-..."); the last line has a space after the code ("250 ...").
    $talk = function (?string $command, array $okCodes) use ($socket): void {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }
        $reply = '';
        while (($line = fgets($socket, 515)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        if (!in_array((int) substr($reply, 0, 3), $okCodes, true)) {
            throw new RuntimeException(trim($reply) ?: 'no reply from server');
        }
    };

    try {
        $talk(null, [220]);                       // server greeting
        $talk('EHLO mendly.local', [250]);

        if ($encryption === 'tls') {              // upgrade to an encrypted connection
            $talk('STARTTLS', [220]);
            $methods = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                | (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT : 0);
            if (!stream_socket_enable_crypto($socket, true, $methods)) {
                throw new RuntimeException('TLS handshake failed');
            }
            $talk('EHLO mendly.local', [250]);
        }

        if ($username !== '') {
            $talk('AUTH LOGIN', [334]);
            $talk(base64_encode($username), [334]);
            $talk(base64_encode($password), [235]);
        }

        $talk("MAIL FROM:<$from>", [250]);
        $talk("RCPT TO:<$to>", [250, 251]);
        $talk('DATA', [354]);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . " <$from>",
            "To: <$to>",
            'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . substr(strrchr($from, '@'), 1) . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        // Normalise line endings, and double any dot at the start of a line
        // (a line holding a single "." would otherwise end the message).
        $text = preg_replace('/\r\n|\r|\n/', "\r\n", $body);
        $text = preg_replace('/^\./m', '..', $text);

        $talk(implode("\r\n", $headers) . "\r\n\r\n" . $text . "\r\n.", [250]);
    } catch (Throwable $error) {
        error_log('Mendly mail: ' . $error->getMessage());
        fclose($socket);
        return false;
    }

    // The message is accepted at this point; a missing goodbye doesn't matter
    try {
        $talk('QUIT', [221]);
    } catch (Throwable $ignored) {
    }
    fclose($socket);
    return true;
}
