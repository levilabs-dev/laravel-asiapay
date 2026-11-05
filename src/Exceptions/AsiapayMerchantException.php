<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Exceptions;

use InvalidArgumentException;

final class AsiapayMerchantException extends InvalidArgumentException
{
    public static function unknownMerchant(string $merchant): self
    {
        return new self("AsiaPay merchant [{$merchant}] is not configured.");
    }

    public static function missingCredentials(string $merchant): self
    {
        return new self(
            "AsiaPay merchant [{$merchant}] is missing app_key, app_secret, private_key, app_id, or merch_code."
        );
    }
}
