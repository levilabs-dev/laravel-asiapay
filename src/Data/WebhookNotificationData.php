<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Data;

use LeviLabs\LaravelAsiapay\Enums\TradeStatus;

final readonly class WebhookNotificationData
{
    public function __construct(
        public string $merchOrderId,
        public TradeStatus $tradeStatus,
        public string $totalAmount,
        public string $currency,
        public ?string $appId = null,
        public ?string $merchCode = null,
        public ?string $paymentOrderId = null,
        public ?string $transId = null,
        public ?string $notifyTime = null,
        public ?string $transEndTime = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            merchOrderId: is_string($payload['merch_order_id'] ?? null) ? $payload['merch_order_id'] : '',
            tradeStatus: TradeStatus::fromGateway(is_string($payload['trade_status'] ?? null) ? $payload['trade_status'] : null),
            totalAmount: is_string($payload['total_amount'] ?? null) ? $payload['total_amount'] : '0',
            currency: is_string($payload['trans_currency'] ?? null) ? $payload['trans_currency'] : 'IQD',
            appId: is_string($payload['appid'] ?? null) ? $payload['appid'] : null,
            merchCode: is_string($payload['merch_code'] ?? null) ? $payload['merch_code'] : null,
            paymentOrderId: is_string($payload['payment_order_id'] ?? null) ? $payload['payment_order_id'] : null,
            transId: is_string($payload['trans_id'] ?? null) ? $payload['trans_id'] : null,
            notifyTime: is_string($payload['notify_time'] ?? null) ? $payload['notify_time'] : null,
            transEndTime: is_string($payload['trans_end_time'] ?? null) ? $payload['trans_end_time'] : null,
            raw: $payload,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->tradeStatus->isSuccessful();
    }
}
