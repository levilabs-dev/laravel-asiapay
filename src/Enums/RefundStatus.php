<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Enums;

enum RefundStatus: string
{
    case RefundSuccess = 'REFUND_SUCCESS';

    case RefundFail = 'REFUND_FAIL';

    case RefundProcessing = 'REFUND_PROCESSING';

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
        return $this === self::RefundSuccess;
    }
}
