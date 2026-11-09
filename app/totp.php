<?php
declare(strict_types=1);

/** Minimal RFC 6238 TOTP (SHA-1, 6 digits, 30 s), compatible with Google Authenticator, Authy, Microsoft Authenticator. */

const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function base32_encode(string $bin): string
{
    $bits = '';
    foreach (str_split($bin) as $c) {
        $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 5) as $chunk) {
        $out .= B32[bindec(str_pad($chunk, 5, '0'))];
    }
    return $out;
}

function base32_decode(string $s): string
{
    $s = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $s));
    $bits = '';
    foreach (str_split($s) as $c) {
        $bits .= str_pad(decbin(strpos(B32, $c)), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $out .= chr(bindec($byte));
        }
    }
    return $out;
}

function totp_new_secret(): string
{
    return base32_encode(random_bytes(20));
}

function totp_code(string $secret, ?int $time = null, int $offset = 0): string
{
    $counter = intdiv($time ?? time(), 30) + $offset;
    $hash = hash_hmac('sha1', pack('J', $counter), base32_decode($secret), true);
    $o = ord($hash[19]) & 0x0F;
    $n = ((ord($hash[$o]) & 0x7F) << 24) | (ord($hash[$o + 1]) << 16) | (ord($hash[$o + 2]) << 8) | ord($hash[$o + 3]);
    return str_pad((string) ($n % 1000000), 6, '0', STR_PAD_LEFT);
}

function totp_verify(string $secret, string $code): bool
{
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }
    for ($i = -1; $i <= 1; $i++) {
        if (hash_equals(totp_code($secret, null, $i), $code)) {
            return true;
        }
    }
    return false;
}

function totp_uri(string $secret, string $account, string $issuer): string
{
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&digits=6&period=30';
}
