<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Services;

use LeviLabs\LaravelAsiapay\Contracts\AsiapayPaymentServiceContract;
use LeviLabs\LaravelAsiapay\Data\OrderStatusData;
use LeviLabs\LaravelAsiapay\Data\PaymentData;
use LeviLabs\LaravelAsiapay\Data\PreOrderRequest;
use LeviLabs\LaravelAsiapay\Data\RefundData;
use LeviLabs\LaravelAsiapay\Data\WebhookNotificationData;
use LeviLabs\LaravelAsiapay\Events\PaymentCreated;
use LeviLabs\LaravelAsiapay\Events\PaymentRefunded;
use LeviLabs\LaravelAsiapay\Events\PaymentStatusChecked;
use LeviLabs\LaravelAsiapay\Events\WebhookReceived;
use LeviLabs\LaravelAsiapay\Exceptions\AsiapayException;
use LeviLabs\LaravelAsiapay\Services\Concerns\TalksToAsiapay;
use LeviLabs\LaravelAsiapay\Support\WebhookVerifier;

final class AsiapayPaymentService implements AsiapayPaymentServiceContract
{
    use TalksToAsiapay;

    public function createOrder(PreOrderRequest $order, ?string $merchant = null): PaymentData
    {
        if ($order->totalAmount <= 0) {
            throw AsiapayException::invalidAmount($order->totalAmount);
        }

        $merchant ??= $this->defaultMerchant();
        $config = $this->merchantConfig($merchant);

        $redirectUrl = $order->redirectUrl ?? $config['redirect_url'];
        $notifyUrl = $order->notifyUrl ?? $config['notify_url'];

        if ($redirectUrl === null || $redirectUrl === '') {
            throw AsiapayException::missingRedirectUrl();
        }

        if ($notifyUrl === null || $notifyUrl === '') {
            throw AsiapayException::missingNotifyUrl();
        }

        $bizContent = array_filter([
            'appid' => $config['app_id'],
            'business_type' => $order->businessType ?? (string) config('asiapay.business_type', 'BuyGoods'),
            'merch_code' => $config['merch_code'],
            'merch_order_id' => $order->merchOrderId,
            'redirect_url' => $redirectUrl,
            'notify_url' => $notifyUrl,
            'timeout_express' => $order->timeoutExpress ?? (string) config('asiapay.timeout_express', '30m'),
            'title' => $order->title,
            'total_amount' => $this->formatAmount($order->totalAmount),
            'trade_type' => $order->tradeType->value,
            'trans_currency' => $order->currency ?? (string) config('asiapay.currency', 'IQD'),
            'wallet_reference_data' => $order->walletReferenceData,
        ], fn ($value) => $value !== null);

        $response = $this->signedPost(
            $merchant,
            'preOrder',
            'payment/gateway/payment/v1/merchant/preOrder',
            'payment.preorder',
            $bizContent,
        );

        $payment = PaymentData::fromApiResponse($response);

        PaymentCreated::dispatch($payment, $merchant, $order->totalAmount, $order->title);

        return $payment;
    }

    public function queryOrder(string $merchOrderId, ?string $merchant = null): OrderStatusData
    {
        $merchant ??= $this->defaultMerchant();
        $config = $this->merchantConfig($merchant);

        $bizContent = [
            'appid' => $config['app_id'],
            'merch_code' => $config['merch_code'],
            'merch_order_id' => $merchOrderId,
        ];

        $response = $this->signedPost(
            $merchant,
            'queryOrder',
            'payment/gateway/payment/v1/merchant/queryOrder',
            'payment.queryorder',
            $bizContent,
        );

        $status = OrderStatusData::fromApiResponse($response);

        PaymentStatusChecked::dispatch($status, $merchant);

        return $status;
    }

    public function refund(
        string $merchOrderId,
        string $refundRequestNo,
        string $refundReason,
        ?string $merchant = null,
    ): RefundData {
        $merchant ??= $this->defaultMerchant();
        $config = $this->merchantConfig($merchant);

        $bizContent = [
            'appid' => $config['app_id'],
            'merch_code' => $config['merch_code'],
            'merch_order_id' => $merchOrderId,
            'refund_request_no' => $refundRequestNo,
            'refund_reason' => $refundReason,
        ];

        $response = $this->signedPost(
            $merchant,
            'refund',
            'payment/gateway/payment/v1/merchant/refund',
            'payment.refund',
            $bizContent,
        );

        $refund = RefundData::fromApiResponse($response);

        PaymentRefunded::dispatch($refund, $merchant, $refundRequestNo);

        return $refund;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhook(array $payload, ?string $merchant = null): bool
    {
        $merchant ??= $this->defaultMerchant();
        $config = $this->merchantConfig($merchant);

        if ($config['private_key'] === '') {
            throw AsiapayException::missingPrivateKey($merchant);
        }

        return WebhookVerifier::verify($payload, $config['private_key']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload, ?string $merchant = null): WebhookNotificationData
    {
        if (! $this->verifyWebhook($payload, $merchant)) {
            throw new AsiapayException('AsiaPay webhook signature verification failed.');
        }

        $notification = WebhookNotificationData::fromPayload($payload);

        WebhookReceived::dispatch($notification, $merchant ?? $this->defaultMerchant());

        return $notification;
    }

    public function webhookAckBody(): string
    {
        return (string) config('asiapay.webhook.ack_body', 'SUCCESS');
    }
}
