<?php

declare(strict_types=1);

use LeviLabs\LaravelAsiapay\Support\JwtSigner;
use LeviLabs\LaravelAsiapay\Tests\TestCase;

it('signs and verifies an API envelope', function () {
    $privateKey = TestCase::TEST_PRIVATE_KEY;

    $envelope = [
        'biz_content' => [
            'merch_order_id' => 'ORDER-1',
            'total_amount' => '50.00',
        ],
        'method' => 'payment.preorder',
        'nonce_str' => 'abc123',
        'sign_type' => 'JWTSecret',
        'timestamp' => '1746601183',
        'version' => '1.0',
    ];

    $signed = $envelope;
    $signed['sign'] = JwtSigner::sign($envelope, $privateKey);

    expect(JwtSigner::verify($signed, $privateKey))->toBeTrue();
});

it('rejects a tampered webhook payload', function () {
    $privateKey = TestCase::TEST_PRIVATE_KEY;

    $payload = [
        'merch_order_id' => '19328238601',
        'trade_status' => 'Completed',
        'total_amount' => '1.000',
        'trans_currency' => 'IQD',
        'sign_type' => 'JWTSecret',
    ];

    $payload['sign'] = JwtSigner::sign($payload, $privateKey);
    $payload['total_amount'] = '999.000';

    expect(JwtSigner::verify($payload, $privateKey))->toBeFalse();
});
