<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Data;

use LeviLabs\LaravelAsiapay\Enums\RefundStatus;

final readonly class RefundData
{
    public function __construct(
        public string $merchOrderId,
        public RefundStatus $refundStatus,
        public ?string $refundOrderId = null,
        public ?string $refundAmount = null,
        public ?string $refundCurrency = null,
        public ?string $refundTime = null,
        public ?string $transOrderId = null,
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
            refundStatus: RefundStatus::fromGateway(is_string($biz['refund_status'] ?? null) ? $biz['refund_status'] : null),
            refundOrderId: is_string($biz['refund_order_id'] ?? null) ? $biz['refund_order_id'] : null,
            refundAmount: is_string($biz['refund_amount'] ?? null) ? $biz['refund_amount'] : null,
            refundCurrency: is_string($biz['refund_currency'] ?? null) ? $biz['refund_currency'] : null,
            refundTime: is_string($biz['refund_time'] ?? null) ? $biz['refund_time'] : null,
            transOrderId: is_string($biz['trans_order_id'] ?? null) ? $biz['trans_order_id'] : null,
            raw: $response,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->refundStatus->isSuccessful();
    }
}
