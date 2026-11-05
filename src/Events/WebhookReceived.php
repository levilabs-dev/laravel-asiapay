<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LeviLabs\LaravelAsiapay\Data\WebhookNotificationData;

final class WebhookReceived
{
    use Dispatchable;

    public function __construct(
        public readonly WebhookNotificationData $notification,
        public readonly string $merchant,
    ) {}
}
