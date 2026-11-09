<?php
declare(strict_types=1);

/**
 * WhatsApp delivery via Twilio's WhatsApp API. Twilio's Sandbox is free to test with:
 * create a Twilio account, open Messaging > Try it out > Send a WhatsApp message, and
 * send "join <your-sandbox-word>" from the phone you want to test to the sandbox number
 * shown there. After that, messages sent to that phone arrive on the device immediately —
 * no payment needed to try it. Going live with your own WhatsApp number later uses the
 * same code, just with production credentials instead of sandbox ones.
 *
 * @return array{0: bool, 1: ?string, 2: ?string, 3: bool} [ok, provider_message_id, error, retryable]
 */
function whatsapp_send(array $cfg, string $toPhoneE164, string $message): array
{
    $sid = $cfg['twilio_sid'] ?? ''; $token = $cfg['twilio_token'] ?? ''; $from = $cfg['twilio_whatsapp_from'] ?? '';
    if ($sid === '' || $token === '' || $from === '') {
        return [false, null, 'WhatsApp is not configured (Twilio SID / token / sender number)', false];
    }
    $ch = curl_init(((string) cfg('twilio_api_base') ?: 'https://api.twilio.com') . "/2010-04-01/Accounts/$sid/Messages.json");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_USERPWD => "$sid:$token",
        CURLOPT_POSTFIELDS => http_build_query([
            'From' => 'whatsapp:' . $from,
            'To' => 'whatsapp:' . $toPhoneE164,
            'Body' => $message,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno || $resp === false) {
        return [false, null, 'Network error contacting WhatsApp gateway', true];
    }
    $data = json_decode((string) $resp, true);
    if ($httpCode >= 200 && $httpCode < 300 && !empty($data['sid'])) {
        return [true, $data['sid'], null, false];
    }
    // 429 / 5xx / 408 are temporary on the provider side; other 4xx (bad number, bad credentials) will not fix themselves
    $retryable = $httpCode === 429 || $httpCode === 408 || $httpCode >= 500;
    return [false, null, (string) ($data['message'] ?? ('HTTP ' . $httpCode)), $retryable];
}
