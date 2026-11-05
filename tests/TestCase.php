<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Tests;

use LeviLabs\LaravelAsiapay\AsiapayServiceProvider;
use LeviLabs\LaravelAsiapay\Support\JwtSigner;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    public const TEST_PRIVATE_KEY = 'sandbox-private-key-for-tests-only';

    protected function getPackageProviders($app): array
    {
        return [
            AsiapayServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('asiapay.merchants.default', [
            'environment' => 'sandbox',
            'base_url' => 'https://apitest.asiapay.iq:5443/apiaccess',
            'app_key' => 'test-app-key',
            'app_secret' => 'test-app-secret',
            'private_key' => self::TEST_PRIVATE_KEY,
            'app_id' => '1170344370329602',
            'merch_code' => '260224',
            'redirect_url' => 'https://merchant.test/return',
            'notify_url' => 'https://merchant.test/webhook',
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
    }

    /**
     * @param  array<string, mixed>  $bizContent
     * @return array<string, mixed>
     */
    protected function signedApiEnvelope(array $bizContent, ?string $privateKey = null): array
    {
        $privateKey ??= self::TEST_PRIVATE_KEY;

        $envelope = [
            'result' => 'SUCCESS',
            'code' => '0',
            'msg' => 'success',
            'nonce_str' => '8941e36049bf4c2a9534a5ea11f81e51',
            'sign_type' => 'JWTSecret',
            'biz_content' => $bizContent,
        ];

        $envelope['sign'] = JwtSigner::sign($envelope, $privateKey);

        return $envelope;
    }
}
