<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Enums;

/**
 * trade_status on payment webhooks (documented example: Completed).
 */
enum TradeStatus: string
{
    case Completed = 'Completed';

    case Unknown = 'UNKNOWN';

    public static function fromGateway(?string $value): self
    {
        if ($value === null || $value === '') {
            return self::Unknown;
        }

        return self::tryFrom($value) ?? self::Unknown;
    }

    public function isSuccessful(): bool
    {
        return $this === self::Completed;
    }
}
