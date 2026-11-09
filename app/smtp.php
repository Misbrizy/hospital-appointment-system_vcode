<?php
declare(strict_types=1);

/**
 * A minimal, dependency-free SMTP client — no PHPMailer/Composer needed, so it runs on
 * plain shared hosting too. Supports STARTTLS (port 587) and implicit TLS (port 465),
 * AUTH LOGIN, and a plain-text or simple HTML body. This is what actually makes email
 * reminders land in the inbox quickly, instead of relying on PHP's often-broken mail().
 */

/** @return array{0: bool, 1: ?string, 2: bool} [ok, error, temporary] — temporary=true means a retry may succeed (network trouble, 4xx replies) */
function smtp_send(array $cfg, string $toEmail, string $toName, string $subject, string $body, bool $isHtml = false): array
{
    $host = $cfg['host'] ?? ''; $port = (int) ($cfg['port'] ?? 587);
    $user = $cfg['username'] ?? ''; $pass = $cfg['password'] ?? '';
    $fromEmail = $cfg['from_email'] ?? $user; $fromName = $cfg['from_name'] ?? cfg('app_name');
    $encryption = $cfg['encryption'] ?? ($port === 465 ? 'ssl' : 'tls');

    if ($host === '' || $fromEmail === '') {
        return [false, 'SMTP is not configured', false];
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $sock = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$sock) {
        return [false, "Could not connect to $host:$port ($errstr)", true];
    }
    stream_set_timeout($sock, 15);

    $read = function () use ($sock): string {
        $data = '';
        while (($line = fgets($sock, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') { break; } // "250 " ends a multi-line reply; "250-" continues
        }
        return $data;
    };
    $write = function (string $cmd) use ($sock): void { fwrite($sock, $cmd . "\r\n"); };
    $code = function (string $resp): int { return (int) substr($resp, 0, 3); };
    // No reply at all (timeout / dropped connection) or a 4xx reply is worth retrying; a 5xx reply is final.
    $temporary = function (int $c): bool { return $c === 0 || ($c >= 400 && $c < 500); };

    $greeting = $read();
    if ($code($greeting) !== 220) { fclose($sock); return [false, 'No SMTP greeting: ' . trim($greeting), true]; }

    $write('EHLO ' . (parse_url(cfg('app_url'), PHP_URL_HOST) ?: 'localhost'));
    $ehlo = $read();
    if ($code($ehlo) !== 250) { fclose($sock); return [false, 'EHLO failed: ' . trim($ehlo), $temporary($code($ehlo))]; }

    if ($encryption === 'tls') {
        $write('STARTTLS');
        $resp = $read();
        if ($code($resp) !== 220) { fclose($sock); return [false, 'STARTTLS refused: ' . trim($resp), false]; }
        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($sock);
            return [false, 'TLS handshake failed', true];
        }
        $write('EHLO ' . (parse_url(cfg('app_url'), PHP_URL_HOST) ?: 'localhost'));
        $read(); // discard second EHLO reply
    }

    if ($user !== '') {
        $write('AUTH LOGIN');
        if ($code($read()) !== 334) { fclose($sock); return [false, 'AUTH LOGIN not accepted', false]; }
        $write(base64_encode($user));
        if ($code($read()) !== 334) { fclose($sock); return [false, 'Username rejected', false]; }
        $write(base64_encode($pass));
        $authResp = $read();
        if ($code($authResp) !== 235) { fclose($sock); return [false, 'Authentication failed: ' . trim($authResp), $temporary($code($authResp))]; }
    }

    $write('MAIL FROM:<' . $fromEmail . '>');
    if ($code($read()) !== 250) { fclose($sock); return [false, 'MAIL FROM rejected', false]; }
    $write('RCPT TO:<' . $toEmail . '>');
    $rcpt = $read();
    if (!in_array($code($rcpt), [250, 251], true)) { fclose($sock); return [false, 'Recipient rejected: ' . trim($rcpt), $temporary($code($rcpt))]; }
    $write('DATA');
    if ($code($read()) !== 354) { fclose($sock); return [false, 'DATA not accepted', false]; }

    $boundaryDate = date('r');
    $headers = [
        'Date: ' . $boundaryDate,
        'From: ' . mime_header_encode($fromName) . ' <' . $fromEmail . '>',
        'To: ' . ($toName !== '' ? mime_header_encode($toName) . ' <' . $toEmail . '>' : $toEmail),
        'Subject: ' . mime_header_encode($subject),
        'MIME-Version: 1.0',
        'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $escapedBody = preg_replace('/^\./m', '..', $body); // dot-stuffing per SMTP spec
    $write(implode("\r\n", $headers) . "\r\n\r\n" . $escapedBody . "\r\n.");
    $dataResp = $read();
    $write('QUIT');
    fclose($sock);

    if ($code($dataResp) !== 250) {
        return [false, 'Message rejected: ' . trim($dataResp), $temporary($code($dataResp))];
    }
    return [true, null, false];
}

function mime_header_encode(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}
