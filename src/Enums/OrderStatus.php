<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Enums;

/**
 * order_status from payment.queryorder (documented example: PAY_SUCCESS).
 */
enum OrderStatus: string
{
    case PaySuccess = 'PAY_SUCCESS';

    case PayFail = 'PAY_FAIL';

    case PayPending = 'PAY_PENDING';

    case PayClosed = 'PAY_CLOSED';

    case Unknown = 'UNKNOWN';

    public static function fromGateway(?string $value): self
    {
        if ($value === null || $value === '') {
            return self::Unknown;
        }

        return self::tryFrom($value) ?? self::Unknown;
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::PaySuccess, self::PayFail, self::PayClosed => true,
            default => false,
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::PaySuccess;
    }
}
