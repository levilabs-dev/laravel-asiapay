<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Listeners;

use LeviLabs\LaravelAsiapay\Enums\OrderStatus;
use LeviLabs\LaravelAsiapay\Events\PaymentCreated;
use LeviLabs\LaravelAsiapay\Events\PaymentRefunded;
use LeviLabs\LaravelAsiapay\Events\PaymentStatusChecked;
use LeviLabs\LaravelAsiapay\Events\WebhookReceived;
use LeviLabs\LaravelAsiapay\Models\AsiapayPayment;
use LeviLabs\LaravelAsiapay\Models\AsiapayRefund;

final class PersistAsiapayPayment
{
    public function onCreated(PaymentCreated $event): void
    {
        $payment = $event->payment;

        AsiapayPayment::query()->updateOrCreate(
            ['merch_order_id' => $payment->merchOrderId],
            [
                'merchant' => $event->merchant,
                'prepay_id' => $payment->prepayId,
                'order_status' => OrderStatus::Unknown,
                'amount' => $event->amount,
                'currency' => (string) config('asiapay.currency', 'IQD'),
                'title' => $event->title,
                'redirect_url' => $payment->redirectUrl,
                'meta' => $payment->raw,
            ],
        );
    }

    public function onStatusChecked(PaymentStatusChecked $event): void
    {
        $status = $event->status;

        AsiapayPayment::query()->updateOrCreate(
            ['merch_order_id' => $status->merchOrderId],
            array_filter([
                'merchant' => $event->merchant,
                'order_status' => $status->orderStatus,
                'amount' => (float) $status->totalAmount,
                'currency' => $status->currency,
                'trans_id' => $status->transId,
                'payment_order_id' => $status->paymentOrderId,
                'meta' => $status->raw,
                'checked_at' => now(),
            ], fn ($value) => $value !== null),
        );
    }

    public function onWebhook(WebhookReceived $event): void
    {
        $notification = $event->notification;

        AsiapayPayment::query()->updateOrCreate(
            ['merch_order_id' => $notification->merchOrderId],
            array_filter([
                'merchant' => $event->merchant,
                'trade_status' => $notification->tradeStatus,
                'amount' => (float) $notification->totalAmount,
                'currency' => $notification->currency,
                'trans_id' => $notification->transId,
                'payment_order_id' => $notification->paymentOrderId,
                'meta' => $notification->raw,
                'checked_at' => now(),
            ], fn ($value) => $value !== null),
        );
    }

    public function onRefunded(PaymentRefunded $event): void
    {
        $refund = $event->refund;

        $payment = AsiapayPayment::query()->where('merch_order_id', $refund->merchOrderId)->first();

        if ($payment === null) {
            return;
        }

        AsiapayRefund::query()->updateOrCreate(
            [
                'asiapay_payment_id' => $payment->id,
                'refund_request_no' => $event->refundRequestNo,
            ],
            array_filter([
                'refund_order_id' => $refund->refundOrderId,
                'status' => $refund->refundStatus,
                'amount' => $refund->refundAmount !== null ? (float) $refund->refundAmount : null,
                'currency' => $refund->refundCurrency ?? 'IQD',
                'meta' => $refund->raw,
            ], fn ($value) => $value !== null),
        );
    }
}
