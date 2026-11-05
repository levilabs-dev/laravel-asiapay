<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Data;

use LeviLabs\LaravelAsiapay\Enums\TradeType;

final readonly class PreOrderRequest
{
    /**
     * @param  array<string, mixed>|null  $walletReferenceData
     */
    public function __construct(
        public string $merchOrderId,
        public float $totalAmount,
        public string $title,
        public ?string $redirectUrl = null,
        public ?string $notifyUrl = null,
        public ?string $timeoutExpress = null,
        public TradeType $tradeType = TradeType::Checkout,
        public ?string $businessType = null,
        public ?string $currency = null,
        public ?array $walletReferenceData = null,
    ) {}
}
