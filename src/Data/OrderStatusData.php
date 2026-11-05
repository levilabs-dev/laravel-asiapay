<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Data;

use LeviLabs\LaravelAsiapay\Enums\OrderStatus;

final readonly class OrderStatusData
{
    public function __construct(
        public string $merchOrderId,
        public OrderStatus $orderStatus,
        public string $totalAmount,
        public string $currency,
        public ?string $transId = null,
        public ?string $paymentOrderId = null,
        public ?string $transTime = null,
        /** @var array<string, mixed>|null */
        public ?array $raw = null,
    ) {}

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromApiResponse(array $response): self
    {
        $biz = $response['biz_content'] ?? [];
        if (! is_array($biz)) {
            $biz = [];
        }

        return new self(
            merchOrderId: is_string($biz['merch_order_id'] ?? null) ? $biz['merch_order_id'] : '',
            orderStatus: OrderStatus::fromGateway(is_string($biz['order_status'] ?? null) ? $biz['order_status'] : null),
            totalAmount: is_string($biz['total_amount'] ?? null) ? $biz['total_amount'] : '0',
            currency: is_string($biz['trans_currency'] ?? null) ? $biz['trans_currency'] : 'IQD',
            transId: is_string($biz['trans_id'] ?? null) ? $biz['trans_id'] : null,
            paymentOrderId: is_string($biz['payment_order_id'] ?? null) ? $biz['payment_order_id'] : null,
            transTime: is_string($biz['trans_time'] ?? null) ? $biz['trans_time'] : null,
            raw: $response,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->orderStatus->isSuccessful();
    }
}
