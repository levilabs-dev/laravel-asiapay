<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelAsiapay\Data\PaymentData;

final class PaymentCreated
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentData $payment,
        public readonly string $merchant,
        public readonly float $amount,
        public readonly string $title,
    ) {}
}
