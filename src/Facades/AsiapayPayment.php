<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Facades;

use Illuminate\Support\Facades\Facade;
use LeviLabs\LaravelAsiapay\Contracts\AsiapayPaymentServiceContract;
use LeviLabs\LaravelAsiapay\Services\AsiapayPaymentService;

/**
 * @method static \LeviLabs\LaravelAsiapay\Data\PaymentData createOrder(\LeviLabs\LaravelAsiapay\Data\PreOrderRequest $order, ?string $merchant = null)
 * @method static \LeviLabs\LaravelAsiapay\Data\OrderStatusData queryOrder(string $merchOrderId, ?string $merchant = null)
 * @method static \LeviLabs\LaravelAsiapay\Data\RefundData refund(string $merchOrderId, string $refundRequestNo, string $refundReason, ?string $merchant = null)
 * @method static bool verifyWebhook(array<string, mixed> $payload, ?string $merchant = null)
 * @method static \LeviLabs\LaravelAsiapay\Data\WebhookNotificationData parseWebhook(array<string, mixed> $payload, ?string $merchant = null)
 * @method static string webhookAckBody()
 *
 * @see AsiapayPaymentService
 */
final class AsiapayPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AsiapayPaymentServiceContract::class;
    }
}
