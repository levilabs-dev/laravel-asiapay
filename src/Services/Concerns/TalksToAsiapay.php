<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LeviLabs\LaravelAsiapay\Exceptions\AsiapayException;
use LeviLabs\LaravelAsiapay\Exceptions\AsiapayMerchantException;
use LeviLabs\LaravelAsiapay\Support\JwtSigner;
use LeviLabs\LaravelAsiapay\Support\TokenNormalizer;

trait TalksToAsiapay
{
    private const TOKEN_PATH = 'payment/gateway/payment/v1/token';

    /**
     * @return array{
     *     environment: string,
     *     base_url: string,
     *     app_key: string,
     *     app_secret: string,
     *     private_key: string,
     *     app_id: string,
     *     merch_code: string,
     *     redirect_url: ?string,
     *     notify_url: ?string
     * }
     */
    private function merchantConfig(string $merchant): array
    {
        $config = config("asiapay.merchants.{$merchant}");

        if (! is_array($config)) {
            throw AsiapayMerchantException::unknownMerchant($merchant);
        }

        $required = ['app_key', 'app_secret', 'private_key', 'app_id', 'merch_code'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw AsiapayMerchantException::missingCredentials($merchant);
            }
        }

        return [
            'environment' => (string) ($config['environment'] ?? 'sandbox'),
            'base_url' => rtrim((string) ($config['base_url'] ?? 'https://apitest.asiapay.iq:5443/apiaccess'), '/'),
            'app_key' => (string) $config['app_key'],
            'app_secret' => (string) $config['app_secret'],
            'private_key' => (string) $config['private_key'],
            'app_id' => (string) $config['app_id'],
            'merch_code' => (string) $config['merch_code'],
            'redirect_url' => isset($config['redirect_url']) && $config['redirect_url'] !== ''
                ? (string) $config['redirect_url']
                : null,
            'notify_url' => isset($config['notify_url']) && $config['notify_url'] !== ''
                ? (string) $config['notify_url']
                : null,
        ];
    }

    private function defaultMerchant(): string
    {
        return (string) config('asiapay.default', 'default');
    }

    private function httpClient(string $merchant, ?string $accessToken = null): PendingRequest
    {
        $config = $this->merchantConfig($merchant);

        $request = Http::baseUrl($config['base_url'].'/')
            ->withHeaders(['X-APP-Key' => $config['app_key']])
            ->timeout((int) config('asiapay.http.timeout', 30))
            ->retry(
                (int) config('asiapay.http.retry_times', 1),
                (int) config('asiapay.http.retry_sleep_ms', 200),
            )
            ->acceptJson()
            ->asJson();

        if ($accessToken !== null && $accessToken !== '') {
            $request = $request->withToken(TokenNormalizer::stripBearerPrefix($accessToken));
        }

        return $request;
    }

    private function tokenCacheKey(string $merchant): string
    {
        return "asiapay.token.{$merchant}";
    }

    public function forgetAccessToken(?string $merchant = null): void
    {
        Cache::forget($this->tokenCacheKey($merchant ?? $this->defaultMerchant()));
    }

    private function rememberAccessToken(string $merchant, callable $resolver): string
    {
        $key = $this->tokenCacheKey($merchant);
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $resolver();
        if (! is_string($token) || $token === '') {
            throw AsiapayException::missingAccessToken();
        }

        Cache::put($key, $token, (int) config('asiapay.token_cache_ttl_seconds', 3300));

        return $token;
    }

    private function fetchAccessToken(string $merchant): string
    {
        $config = $this->merchantConfig($merchant);

        $response = $this->httpClient($merchant)->post(self::TOKEN_PATH, [
            'appSecret' => $config['app_secret'],
        ]);

        if ($response->failed()) {
            throw AsiapayException::requestFailed('token', null, $response->body());
        }

        $token = $response->json('token');
        if (! is_string($token) || $token === '') {
            throw AsiapayException::missingAccessToken();
        }

        return TokenNormalizer::stripBearerPrefix($token);
    }

    /**
     * @param  array<string, mixed>  $bizContent
     * @return array<string, mixed>
     */
    private function buildSignedEnvelope(string $method, array $bizContent, string $privateKey): array
    {
        $envelope = [
            'biz_content' => $bizContent,
            'method' => $method,
            'nonce_str' => Str::replace('-', '', (string) Str::uuid()),
            'sign_type' => 'JWTSecret',
            'timestamp' => (string) time(),
            'version' => '1.0',
        ];

        $envelope['sign'] = JwtSigner::sign($envelope, $privateKey);

        return $envelope;
    }

    /**
     * @param  array<string, mixed>  $bizContent
     * @return array<string, mixed>
     */
    private function signedPost(
        string $merchant,
        string $action,
        string $path,
        string $apiMethod,
        array $bizContent,
        bool $verifyResponseSign = true,
    ): array {
        $config = $this->merchantConfig($merchant);

        $send = function () use ($merchant, $apiMethod, $bizContent, $config): array {
            $token = $this->rememberAccessToken($merchant, fn (): string => $this->fetchAccessToken($merchant));
            $body = $this->buildSignedEnvelope($apiMethod, $bizContent, $config['private_key']);

            return [$token, $body];
        };

        [$token, $body] = $send();
        $response = $this->httpClient($merchant, $token)->post(ltrim($path, '/'), $body);

        if ($response->status() === 401) {
            $this->forgetAccessToken($merchant);
            [$token, $body] = $send();
            $response = $this->httpClient($merchant, $token)->post(ltrim($path, '/'), $body);
        }

        if ($response->failed()) {
            throw AsiapayException::requestFailed(
                $action,
                is_string($response->json('code')) ? $response->json('code') : null,
                is_string($response->json('msg')) ? $response->json('msg') : $response->body(),
            );
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();
        if (! is_array($json)) {
            return [];
        }

        $this->assertApiSuccess($json, $action);

        if ($verifyResponseSign && isset($json['sign']) && is_string($json['sign']) && $json['sign'] !== '') {
            if (! JwtSigner::verify($json, $config['private_key'])) {
                throw AsiapayException::invalidResponseSignature($action);
            }
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function assertApiSuccess(array $json, string $action): void
    {
        $result = $json['result'] ?? null;
        $code = $json['code'] ?? null;

        $codeOk = $code === 0 || $code === '0';
        $resultOk = $result === 'SUCCESS';

        if (! $resultOk && ! $codeOk) {
            throw AsiapayException::requestFailed(
                $action,
                is_string($code) || is_numeric($code) ? (string) $code : null,
                is_string($json['msg'] ?? null) ? $json['msg'] : null,
            );
        }
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
