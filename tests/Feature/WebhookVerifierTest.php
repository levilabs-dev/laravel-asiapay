<?php

declare(strict_types=1);

use LeviLabs\LaravelAsiapay\Support\JwtSigner;
use LeviLabs\LaravelAsiapay\Support\WebhookVerifier;
use LeviLabs\LaravelAsiapay\Tests\TestCase;

it('verifies a signed webhook notification', function () {
    $privateKey = TestCase::TEST_PRIVATE_KEY;

    $payload = [
        'merch_order_id' => '19328238601',
        'trade_status' => 'Completed',
        'total_amount' => '1250.000',
        'trans_currency' => 'IQD',
        'sign_type' => 'JWTSecret',
    ];

    $payload['sign'] = JwtSigner::sign($payload, $privateKey);

    expect(WebhookVerifier::verify($payload, $privateKey))->toBeTrue()
        ->and(WebhookVerifier::verifyAndMatch($payload, $privateKey, [
            'merch_order_id' => '19328238601',
            'total_amount' => '1250.000',
        ]))->toBeTrue();
});
