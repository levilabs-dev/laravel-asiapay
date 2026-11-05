<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Data;

/**
 * Response to payment.preorder — redirect the payer to redirectUrl.
 */
final readonly class PaymentData
{
    public function __construct(
        public string $merchOrderId,
        public ?string $prepayId,
        public string $redirectUrl,
        public ?string $nonceStr = null,
        /** @var array<string, mixed>|null */
        public ?array $raw = null,
    ) {}

    /**
     * @param  array<string, mixed>  $response  Full API response envelope.
     */
    public static function fromApiResponse(array $response): self
    {
        $biz = $response['biz_content'] ?? [];
        if (! is_array($biz)) {
            $biz = [];
        }

        $redirect = $biz['redirect_url'] ?? null;
        if (! is_string($redirect) || $redirect === '') {
            throw new \InvalidArgumentException('AsiaPay preOrder response is missing biz_content.redirect_url.');
        }

        $merchOrderId = $biz['merch_order_id'] ?? '';
        if (! is_string($merchOrderId)) {
            $merchOrderId = '';
        }

        $prepayId = $biz['prepay_id'] ?? null;

        return new self(
            merchOrderId: $merchOrderId,
            prepayId: is_string($prepayId) ? $prepayId : null,
            redirectUrl: $redirect,
            nonceStr: is_string($response['nonce_str'] ?? null) ? $response['nonce_str'] : null,
            raw: $response,
        );
    }
}
