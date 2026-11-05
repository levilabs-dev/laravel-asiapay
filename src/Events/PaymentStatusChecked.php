<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelAsiapay\Data\OrderStatusData;

final class PaymentStatusChecked
{
    use Dispatchable;

    public function __construct(
        public readonly OrderStatusData $status,
        public readonly string $merchant,
    ) {}
}
