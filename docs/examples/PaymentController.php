<?php

declare(strict_types=1);

/**
 * Reference example for levilabs/laravel-asiapay (Iraq — asiapay.iq only).
 *
 * Copy into your application. Assumes App\Models\Order with `total` and `id`.
 */

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use LeviLabs\LaravelAsiapay\Data\PreOrderRequest;
use LeviLabs\LaravelAsiapay\Enums\TradeType;
use LeviLabs\LaravelAsiapay\Facades\AsiapayPayment;
use LeviLabs\LaravelAsiapay\Models\AsiapayPayment as AsiapayPaymentModel;
use Throwable;

class PaymentController extends Controller
{
    public function pay(Order $order)
    {
        $merchOrderId = 'ORDER-'.$order->id.'-'.time();

        $payment = AsiapayPayment::createOrder(new PreOrderRequest(
            merchOrderId: $merchOrderId,
            totalAmount: (float) $order->total,
            title: 'Order #'.$order->id,
            tradeType: TradeType::Checkout,
        ));

        $order->asiapayPayment()->associate(
            AsiapayPaymentModel::query()->where('merch_order_id', $merchOrderId)->first()
        )->save();

        return redirect($payment->redirectUrl);
    }

    public function webhook(Request $request)
    {
        try {
            $notification = AsiapayPayment::parseWebhook($request->all());
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Invalid notification'], 400);
        }

        if ($notification->isSuccessful()) {
            Order::query()
                ->whereHas('asiapayPayment', fn ($q) => $q->where('merch_order_id', $notification->merchOrderId))
                ->update(['status' => 'paid']);
        }

        return response(AsiapayPayment::webhookAckBody(), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function returned(Order $order)
    {
        $record = AsiapayPaymentModel::query()
            ->where('payable_id', $order->id)
            ->where('payable_type', Order::class)
            ->firstOrFail();

        $status = AsiapayPayment::queryOrder($record->merch_order_id);

        if ($status->isSuccessful()) {
            $order->update(['status' => 'paid']);

            return redirect()->route('orders.show', $order)->with('status', 'Payment successful.');
        }

        return redirect()->route('orders.show', $order)->with('status', 'Payment was not completed.');
    }
}
