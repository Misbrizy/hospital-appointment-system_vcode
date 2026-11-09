<?php
declare(strict_types=1);

function app_key(): string
{
    $k = base64_decode((string) cfg('app_key'), true);
    if ($k === false || strlen($k) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException('APP_KEY is missing or invalid. Run: php bin/install.php');
    }
    return $k;
}

/** Field-level encryption (XSalsa20-Poly1305) for clinical notes and 2FA secrets. */
function encrypt_field(?string $plain): ?string
{
    if ($plain === null || $plain === '') {
        return null;
    }
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, app_key()));
}

function decrypt_field(?string $enc): ?string
{
    if ($enc === null || $enc === '' || !str_starts_with($enc, 'v1:')) {
        return null;
    }
    $raw = base64_decode(substr($enc, 3), true);
    if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return null;
    }
    $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, app_key());
    return $plain === false ? null : $plain;
}
