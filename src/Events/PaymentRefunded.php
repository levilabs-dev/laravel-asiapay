<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelAsiapay\Data\RefundData;

final class PaymentRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly RefundData $refund,
        public readonly string $merchant,
        public readonly string $refundRequestNo,
    ) {}
}
