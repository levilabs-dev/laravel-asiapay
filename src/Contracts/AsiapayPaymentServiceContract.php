<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Contracts;

use LeviLabs\LaravelAsiapay\Data\OrderStatusData;
use LeviLabs\LaravelAsiapay\Data\PaymentData;
use LeviLabs\LaravelAsiapay\Data\PreOrderRequest;
use LeviLabs\LaravelAsiapay\Data\RefundData;
use LeviLabs\LaravelAsiapay\Data\WebhookNotificationData;

interface AsiapayPaymentServiceContract
{
    public function createOrder(PreOrderRequest $order, ?string $merchant = null): PaymentData;

    public function queryOrder(string $merchOrderId, ?string $merchant = null): OrderStatusData;

    public function refund(
        string $merchOrderId,
        string $refundRequestNo,
        string $refundReason,
        ?string $merchant = null,
    ): RefundData;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhook(array $payload, ?string $merchant = null): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload, ?string $merchant = null): WebhookNotificationData;

    public function webhookAckBody(): string;
}
