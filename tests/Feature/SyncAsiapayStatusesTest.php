<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LeviLabs\LaravelAsiapay\Enums\OrderStatus;
use LeviLabs\LaravelAsiapay\Models\AsiapayPayment;

it('syncs non-terminal payments from the API', function () {
    AsiapayPayment::query()->create([
        'merchant' => 'default',
        'merch_order_id' => 'ORDER-SYNC',
        'order_status' => OrderStatus::Unknown,
        'amount' => 25,
        'currency' => 'IQD',
    ]);

    Http::fake([
        '*/payment/gateway/payment/v1/token' => Http::response(['token' => 'sandbox-token'], 200),
        '*/payment/gateway/payment/v1/merchant/queryOrder' => Http::response(
            test()->signedApiEnvelope([
                'merch_order_id' => 'ORDER-SYNC',
                'order_status' => 'PAY_SUCCESS',
                'total_amount' => '25.000',
                'trans_currency' => 'IQD',
            ]),
            200,
        ),
    ]);

    $this->artisan('asiapay:sync-statuses')->assertSuccessful();

    expect(AsiapayPayment::query()->where('merch_order_id', 'ORDER-SYNC')->value('order_status'))
        ->toBe(OrderStatus::PaySuccess);
});
