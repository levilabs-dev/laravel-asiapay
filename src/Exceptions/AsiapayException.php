<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Exceptions;

use InvalidArgumentException;
use RuntimeException;

class AsiapayException extends RuntimeException
{
    public readonly ?string $apiCode;

    public readonly ?string $apiMessage;

    public function __construct(string $message, ?string $apiCode = null, ?string $apiMessage = null)
    {
        parent::__construct($message);

        $this->apiCode = $apiCode;
        $this->apiMessage = $apiMessage;
    }

    public static function requestFailed(string $action, ?string $code, ?string $message): self
    {
        $detail = $message ?? 'Unknown error';
        $label = $code !== null ? "code {$code}" : 'no code';

        return new self("AsiaPay {$action} request failed ({$label}): {$detail}", $code, $message);
    }

    public static function invalidAmount(float $amount): InvalidArgumentException
    {
        return new InvalidArgumentException("Amount must be greater than zero, got {$amount}.");
    }

    public static function missingAccessToken(): self
    {
        return new self('AsiaPay token response did not include an access token.');
    }

    public static function invalidResponseSignature(string $action): self
    {
        return new self("AsiaPay {$action} response signature verification failed.");
    }

    public static function missingPrivateKey(string $merchant): self
    {
        return new self(
            "AsiaPay merchant [{$merchant}] has no private_key configured — required to verify webhooks."
        );
    }

    public static function missingRedirectUrl(): self
    {
        return new self('redirect_url is required (pass explicitly or set ASIAPAY_REDIRECT_URL).');
    }

    public static function missingNotifyUrl(): self
    {
        return new self('notify_url is required (pass explicitly or set ASIAPAY_NOTIFY_URL).');
    }
}
