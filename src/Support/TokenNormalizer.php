<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Support;

final class TokenNormalizer
{
    /**
     * AsiaPay may return `Bearer <token>` in the token field — Laravel's
     * withToken() adds Bearer again unless we strip it first.
     */
    public static function stripBearerPrefix(string $token): string
    {
        $token = trim($token);

        if (str_starts_with(strtolower($token), 'bearer ')) {
            return trim(substr($token, 7));
        }

        return $token;
    }
}
