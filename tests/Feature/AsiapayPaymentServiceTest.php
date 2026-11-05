<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelAsiapay\Contracts\AsiapayPaymentServiceContract;
use LeviLabs\LaravelAsiapay\Data\PreOrderRequest;
use LeviLabs\LaravelAsiapay\Enums\OrderStatus;
use LeviLabs\LaravelAsiapay\Enums\RefundStatus;
use LeviLabs\LaravelAsiapay\Exceptions\AsiapayException;
use LeviLabs\LaravelAsiapay\Models\AsiapayPayment;
use LeviLabs\LaravelAsiapay\Models\AsiapayRefund;
use LeviLabs\LaravelAsiapay\Support\JwtSigner;
use LeviLabs\LaravelAsiapay\Tests\TestCase;

it('creates an order and persists it', function () {
    $redirect = 'https://apitest.asiapay.iq:5443/payment/web/paygate?prepay_id=abc';

    Http::fake([
        '*/payment/gateway/payment/v1/token' => Http::response([
            'token' => 'Bearer sandbox-token',
        ], 200),
        '*/payment/gateway/payment/v1/merchant/preOrder' => Http::response(
            test()->signedApiEnvelope([
                'merch_order_id' => 'ORDER-100',
                'prepay_id' => 'prepay-abc',
                'redirect_url' => $redirect,
            ]),
            200,
        ),
    ]);

    $payment = app(AsiapayPaymentServiceContract::class)->createOrder(new PreOrderRequest(
        merchOrderId: 'ORDER-100',
        totalAmount: 50.0,
        title: 'Test purchase',
    ));

    expect($payment->redirectUrl)->toBe($redirect)
        ->and($payment->merchOrderId)->toBe('ORDER-100');

    expect(AsiapayPayment::query()->where('merch_order_id', 'ORDER-100')->value('amount'))
        ->toEqual(50.0);
});

it('rejects a non-positive amount before sending a request', function () {
    app(AsiapayPaymentServiceContract::class)->createOrder(new PreOrderRequest(
        merchOrderId: 'ORDER-0',
        totalAmount: 0,
        title: 'Invalid',
    ));
})->throws(InvalidArgumentException::class);

it('queries order status and persists it', function () {
    Http::fake([
        '*/payment/gateway/payment/v1/token' => Http::response(['token' => 'sandbox-token'], 200),
        '*/payment/gateway/payment/v1/merchant/queryOrder' => Http::response(
            test()->signedApiEnvelope([
                'merch_order_id' => 'ORDER-200',
                'order_status' => 'PAY_SUCCESS',
                'total_amount' => '1250.000',
                'trans_currency' => 'IQD',
                'trans_id' => '0377830000000660613',
            ]),
            200,
        ),
    ]);

    $status = app(AsiapayPaymentServiceContract::class)->queryOrder('ORDER-200');

    expect($status->isSuccessful())->toBeTrue()
        ->and($status->orderStatus)->toBe(OrderStatus::PaySuccess);

    expect(AsiapayPayment::query()->where('merch_order_id', 'ORDER-200')->value('order_status'))
        ->toBe(OrderStatus::PaySuccess);
});

it('refunds an order and persists the refund row', function () {
    AsiapayPayment::query()->create([
        'merchant' => 'default',
        'merch_order_id' => 'ORDER-300',
        'order_status' => OrderStatus::PaySuccess,
        'amount' => 1250,
        'currency' => 'IQD',
    ]);

    Http::fake([
        '*/payment/gateway/payment/v1/token' => Http::response(['token' => 'sandbox-token'], 200),
        '*/payment/gateway/payment/v1/merchant/refund' => Http::response(
            test()->signedApiEnvelope([
                'merch_order_id' => 'ORDER-300',
                'refund_order_id' => '120010075710435500001001',
                'refund_amount' => '1250.000',
                'refund_currency' => 'IQD',
                'refund_status' => 'REFUND_SUCCESS',
            ]),
            200,
        ),
    ]);

    $refund = app(AsiapayPaymentServiceContract::class)->refund('ORDER-300', 'REF-1', 'Customer request');

    expect($refund->isSuccessful())->toBeTrue()
        ->and($refund->refundStatus)->toBe(RefundStatus::RefundSuccess);

    expect(AsiapayRefund::query()->where('refund_request_no', 'REF-1')->exists())->toBeTrue();
});

it('throws when the API returns a failure envelope', function () {
    Http::fake([
        '*/payment/gateway/payment/v1/token' => Http::response(['token' => 'sandbox-token'], 200),
        '*/payment/gateway/payment/v1/merchant/preOrder' => Http::response([
            'result' => 'FAIL',
            'code' => '1001',
            'msg' => 'Invalid signature',
        ], 200),
    ]);

    app(AsiapayPaymentServiceContract::class)->createOrder(new PreOrderRequest(
        merchOrderId: 'ORDER-FAIL',
        totalAmount: 10.0,
        title: 'Fail',
    ));
})->throws(AsiapayException::class);

it('parses a verified webhook', function () {
    $privateKey = TestCase::TEST_PRIVATE_KEY;

    $payload = [
        'merch_order_id' => 'ORDER-WH',
        'trade_status' => 'Completed',
        'total_amount' => '10.000',
        'trans_currency' => 'IQD',
        'sign_type' => 'JWTSecret',
    ];
    $payload['sign'] = JwtSigner::sign($payload, $privateKey);

    $notification = app(AsiapayPaymentServiceContract::class)->parseWebhook($payload);

    expect($notification->isSuccessful())->toBeTrue();

    expect(AsiapayPayment::query()->where('merch_order_id', 'ORDER-WH')->value('trade_status'))
        ->not->toBeNull();
});
