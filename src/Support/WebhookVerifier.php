<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Support;

/**
 * Verifies AsiaPay payment webhooks (JWT sign in the JSON body).
 */
final class WebhookVerifier
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function verify(array $payload, string $privateKey): bool
    {
        if ($privateKey === '') {
            return false;
        }

        return JwtSigner::verify($payload, $privateKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $expected  Values to match after verification (merch_code, total_amount, …).
     */
    public static function verifyAndMatch(array $payload, string $privateKey, array $expected): bool
    {
        if (! self::verify($payload, $privateKey)) {
            return false;
        }

        foreach ($expected as $key => $value) {
            if (! array_key_exists($key, $payload)) {
                return false;
            }

            if ((string) $payload[$key] !== (string) $value) {
                return false;
            }
        }

        return true;
    }
}
