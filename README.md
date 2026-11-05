# Laravel AsiaPay SDK — Payment Gateway & Webhooks

<p>
<a href="https://packagist.org/packages/levilabs/laravel-asiapay"><img src="https://img.shields.io/packagist/v/levilabs/laravel-asiapay.svg?style=flat-square&label=Packagist&color=orange" alt="Latest Version on Packagist"></a>
<a href="https://github.com/levilabs-dev/laravel-asiapay/actions"><img src="https://img.shields.io/github/actions/workflow/status/levilabs-dev/laravel-asiapay/run-tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/levilabs/laravel-asiapay"><img src="https://img.shields.io/packagist/dt/levilabs/laravel-asiapay.svg?style=flat-square&label=Downloads&color=blue" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/levilabs/laravel-asiapay"><img src="https://img.shields.io/packagist/php-v/levilabs/laravel-asiapay.svg?style=flat-square&label=PHP&color=777bb4" alt="PHP Version"></a>
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?style=flat-square" alt="Laravel Version"></a>
<a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/levilabs/laravel-asiapay.svg?style=flat-square&color=success" alt="License"></a>
</p>

A modern Laravel SDK for the [AsiaPay Payment Gateway](https://www.asiapay.iq/) (Iraq) — **hosted checkout**, JWT-signed merchant API calls, server notify webhooks, order status polling, and refunds. Typed DTOs and enums, cached access tokens, optional database persistence, and an Artisan sync command when notifications are missed.

Maintained by **[Levi Labs](https://levilabs.dev)** ([GitHub](https://github.com/levilabs-dev)) — built by [Nizam Omer](https://nizaamomer.com)

**AsiaPay:** [asiapay.iq](https://www.asiapay.iq/) · **Integration:** [asiapay.iq/integration](https://www.asiapay.iq/integration)

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Creating a payment](#creating-a-payment)
- [The webhook notification](#the-webhook-notification)
- [Checking payment status](#checking-payment-status)
- [Missed notifications: sync command](#missed-notifications-sync-command)
- [Refunding a payment](#refunding-a-payment)
- [Multiple merchants](#multiple-merchants)
- [Automatic persistence](#automatic-persistence)
- [Events](#events)
- [Error handling](#error-handling)
- [Security](#security)
- [Testing](#testing)
- [AsiaPay API reference](#asiapay-api-reference)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Author](#author)
- [License](#license)

## Requirements

- PHP 8.2+ (Laravel **13.x** requires PHP **8.3+** on the application)
- Laravel 11.x, 12.x, or 13.x
- AsiaPay merchant credentials (App Key, App Secret, Private Key, App ID, Merchant Code, Domain URL)

## Installation

```bash
composer require levilabs/laravel-asiapay
php artisan vendor:publish --tag=asiapay-config
php artisan migrate
```

Publish step copies [`config/asiapay.php`](config/asiapay.php). Migrations create `asiapay_payments` and `asiapay_refunds`; lifecycle events upsert rows automatically when listeners are registered (default).

## Configuration

```env
ASIAPAY_MERCHANT=default
ASIAPAY_ENVIRONMENT=sandbox
ASIAPAY_BASE_URL=https://apitest.asiapay.iq:5443/apiaccess
ASIAPAY_APP_KEY=
ASIAPAY_APP_SECRET=
ASIAPAY_PRIVATE_KEY=
ASIAPAY_APP_ID=
ASIAPAY_MERCH_CODE=
ASIAPAY_REDIRECT_URL=https://your-app.test/payments/asiapay/return
ASIAPAY_NOTIFY_URL=https://your-app.test/payments/asiapay/webhook
ASIAPAY_WEBHOOK_ACK=SUCCESS
ASIAPAY_CURRENCY=IQD
ASIAPAY_TIMEOUT_EXPRESS=30m
```

> **Production Domain URL:** the public integration page documents sandbox (`apitest.asiapay.iq`). Production **Domain URL** is issued with your merchant account — set `ASIAPAY_BASE_URL` explicitly before going live.

`redirect_url` and `notify_url` on each order fall back to `ASIAPAY_REDIRECT_URL` and `ASIAPAY_NOTIFY_URL` when omitted on `PreOrderRequest`.

## Creating a payment

Obtains a cached access token, signs a `payment.preorder` request, and returns a **redirect URL** for AsiaPay’s hosted paygate — send the customer there to pay.

```php
use LeviLabs\LaravelAsiapay\Data\PreOrderRequest;
use LeviLabs\LaravelAsiapay\Enums\TradeType;
use LeviLabs\LaravelAsiapay\Facades\AsiapayPayment;

$payment = AsiapayPayment::createOrder(new PreOrderRequest(
    merchOrderId: 'ORDER-'.$order->id.'-'.time(),
    totalAmount: (float) $order->total,
    title: 'Order #'.$order->id,
    tradeType: TradeType::Checkout, // Checkout | CrossApp | Pwa
));

return redirect($payment->redirectUrl);
```

`merch_order_id` must be unique per merchant. Amounts are sent as IQD strings with two decimal places (`trans_currency` defaults to `IQD`).

## The webhook notification

AsiaPay POSTs JSON to your `notify_url` when a payment updates. **Verify the JWT `sign` field before trusting the body:**

```php
use LeviLabs\LaravelAsiapay\Facades\AsiapayPayment;

public function webhook(Request $request)
{
    if (! AsiapayPayment::verifyWebhook($request->all())) {
        return response()->json(['message' => 'Invalid signature'], 400);
    }

    $notification = AsiapayPayment::parseWebhook($request->all());

    if ($notification->isSuccessful()) {
        // trade_status Completed — still confirm amount & merch_order_id against your order
    }

    return response(AsiapayPayment::webhookAckBody(), 200);
}
```

Confirm the exact acknowledgment body with `devops@asiapay.iq` if `SUCCESS` is rejected in your environment (`ASIAPAY_WEBHOOK_ACK`).

## Checking payment status

Authoritative outcome for fulfillment — use from the webhook handler, the return URL after `redirect_url`, or anywhere you need confirmation:

```php
$status = AsiapayPayment::queryOrder('ORDER-123');

$status->orderStatus;   // OrderStatus::PaySuccess | PayFail | …
$status->isSuccessful(); // PAY_SUCCESS
$status->transId;
$status->paymentOrderId;
```

## Missed notifications: sync command

```bash
php artisan asiapay:sync-statuses
```

Re-checks rows in `asiapay_payments` that are not in a terminal `order_status` via `payment.queryorder`. Schedule in `bootstrap/app.php` if you rely on webhooks:

```php
->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
    $schedule->command('asiapay:sync-statuses')->everyFiveMinutes();
})
```

## Refunding a payment

```php
AsiapayPayment::refund(
    merchOrderId: 'ORDER-123',
    refundRequestNo: 'REF-'.time(),
    refundReason: 'Customer request',
);
```

`refund_request_no` must be unique per refund attempt.

## Multiple merchants

Add entries under `merchants` in [`config/asiapay.php`](config/asiapay.php), then pass the merchant key as the last argument:

```php
AsiapayPayment::createOrder($request, merchant: 'second_store');
AsiapayPayment::queryOrder('ORDER-123', merchant: 'second_store');
```

## Automatic persistence

| Table | When rows update |
| ----- | ---------------- |
| `asiapay_payments` | `PaymentCreated`, `PaymentStatusChecked`, `WebhookReceived` |
| `asiapay_refunds` | `PaymentRefunded` |

Optional `payable` morph on `AsiapayPayment` for linking to your `Order` model.

## Events

- `PaymentCreated`
- `PaymentStatusChecked`
- `PaymentRefunded`
- `WebhookReceived`

## Error handling

```php
use LeviLabs\LaravelAsiapay\Exceptions\AsiapayException;
use LeviLabs\LaravelAsiapay\Exceptions\AsiapayMerchantException;

try {
    AsiapayPayment::createOrder($request);
} catch (AsiapayException $e) {
    // API failure (result/code/msg from AsiaPay)
} catch (AsiapayMerchantException $e) {
    // Unknown merchant or missing credentials in config
}
```

## Security

- **Credentials belong in `.env`**, never in version control (`ASIAPAY_APP_SECRET`, `ASIAPAY_PRIVATE_KEY`).
- **Verify webhook signatures** before updating order state; match `merch_order_id`, amount, and currency.
- **Use `queryOrder`** before fulfilling high-value orders — do not rely on redirect URLs alone.
- **TLS verification is never disabled** in this SDK.

See [SECURITY.md](SECURITY.md) for vulnerability reporting.

## Testing

```bash
composer test        # Pest
composer analyse     # PHPStan level 8
composer format      # Laravel Pint
```

## AsiaPay API reference

Official merchant integration guide:

- [AsiaPay](https://www.asiapay.iq/)
- [Integration documentation](https://www.asiapay.iq/integration)
- B2B onboarding: `b2bchannel@asiapay.iq`
- Technical support: `devops@asiapay.iq`

### SDK mapping

| SDK | AsiaPay `method` | HTTP path (under Domain URL) |
| --- | ---------------- | ---------------------------- |
| (token) | — | `POST /payment/gateway/payment/v1/token` |
| `AsiapayPayment::createOrder()` | `payment.preorder` | `POST .../merchant/preOrder` |
| `AsiapayPayment::queryOrder()` | `payment.queryorder` | `POST .../merchant/queryOrder` |
| `AsiapayPayment::refund()` | `payment.refund` | `POST .../merchant/refund` |

| Environment | Base URL (example) |
| ----------- | ------------------ |
| Sandbox | `https://apitest.asiapay.iq:5443/apiaccess` |
| Production | Issued per merchant (set `ASIAPAY_BASE_URL`) |

| Response field | Example |
| -------------- | ------- |
| Query `order_status` | `PAY_SUCCESS` |
| Webhook `trade_status` | `Completed` |
| Refund `refund_status` | `REFUND_SUCCESS` |

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

Bug reports and feature ideas are welcome via [GitHub Issues](https://github.com/levilabs-dev/laravel-asiapay/issues). See [CONTRIBUTING.md](CONTRIBUTING.md).

## Author

**[Levi Labs](https://levilabs.dev)** — [GitHub](https://github.com/levilabs-dev) · [hello@levilabs.dev](mailto:hello@levilabs.dev)

Created and maintained by **Nizam Omer** — [nizaamomer.com](https://nizaamomer.com) · [nizam@nizaamomer.com](mailto:nizam@nizaamomer.com)

## License

MIT — see [LICENSE.md](LICENSE.md).
