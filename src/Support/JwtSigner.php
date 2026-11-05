<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Signs AsiaPay API envelopes and verifies JWT signatures (HS256 + private key).
 *
 * @see https://www.asiapay.iq/integration
 */
final class JwtSigner
{
    /**
     * @param  array<string, mixed>  $payload  Envelope without a sign field.
     */
    public static function sign(array $payload, string $privateKey): string
    {
        return JWT::encode($payload, $privateKey, 'HS256');
    }

    /**
     * @param  array<string, mixed>  $payload  Full payload including sign.
     */
    public static function verify(array $payload, string $privateKey): bool
    {
        $sign = $payload['sign'] ?? null;

        if (! is_string($sign) || $sign === '') {
            return false;
        }

        try {
            JWT::decode($sign, new Key($privateKey, 'HS256'));
        } catch (Throwable) {
            return false;
        }

        $unsigned = $payload;
        unset($unsigned['sign']);

        $expected = self::sign($unsigned, $privateKey);

        return hash_equals($expected, $sign);
    }
}
