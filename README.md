# Laravel AsiaPay SDK — Iraq Payment Gateway (asiapay.iq)

<p>
<a href="https://packagist.org/packages/levilabs/laravel-asiapay"><img src="https://img.shields.io/packagist/v/levilabs/laravel-asiapay.svg?style=flat-square&label=Packagist&color=orange" alt="Latest Version on Packagist"></a>
<a href="https://github.com/levilabs-dev/laravel-asiapay/actions"><img src="https://img.shields.io/github/actions/workflow/status/levilabs-dev/laravel-asiapay/run-tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/levilabs/laravel-asiapay"><img src="https://img.shields.io/packagist/php-v/levilabs/laravel-asiapay.svg?style=flat-square&label=PHP&color=777bb4" alt="PHP Version"></a>
<a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?style=flat-square" alt="Laravel Version"></a>
<a href="LICENSE.md"><img src="https://img.shields.io/packagist/l/levilabs/laravel-asiapay.svg?style=flat-square&color=success" alt="License"></a>
</p>

A modern Laravel SDK for the **[AsiaPay Payment Gateway in Iraq](https://www.asiapay.iq/)** — JWT-signed orders, hosted checkout redirect, status polling, refunds, and verified webhooks. Typed DTOs and enums, cached access tokens, optional DB persistence, and an Artisan sync command.

> **Not Hong Kong AsiaPay:** [asiapay.com](https://www.asiapay.com/) and [developer.asiapay.com](https://developer.asiapay.com/) are a different company (PayDollar). This package is only for **[asiapay.iq](https://www.asiapay.iq/)** (AsiaHawala / CBI-licensed PSP).

Maintained by **[Levi Labs](https://levilabs.dev)** ([GitHub](https://github.com/levilabs-dev)) — built by [Nizam Omer](https://nizaamomer.com)

**Official docs:** [asiapay.iq/integration](https://www.asiapay.iq/integration) · **Support:** `devops@asiapay.iq` · **B2B:** `b2bchannel@asiapay.iq`

## Requirements

- PHP 8.2+ (Laravel **13.x** requires PHP **8.3+** on the application)
- Laravel 11.x, 12.x, or 13.x
- Merchant credentials from AsiaPay Iraq (App Key, App Secret, Private Key, App ID, Merchant Code, Domain URL)

## Installation

```bash
composer require levilabs/laravel-asiapay
php artisan vendor:publish --tag=asiapay-config
php artisan migrate
```

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
```

Production **Domain URL** is issued per merchant during onboarding (not always published on the public integration page).

## Creating a payment

Returns a **redirect URL** for AsiaPay’s hosted paygate.

```php
use LeviLabs\LaravelAsiapay\Data\PreOrderRequest;
use LeviLabs\LaravelAsiapay\Enums\TradeType;
use LeviLabs\LaravelAsiapay\Facades\AsiapayPayment;

$payment = AsiapayPayment::createOrder(new PreOrderRequest(
    merchOrderId: 'ORDER-'.$order->id.'-'.time(),
    totalAmount: (float) $order->total,
    title: 'Order #'.$order->id,
    tradeType: TradeType::Checkout, // or CrossApp, Pwa
));

return redirect($payment->redirectUrl);
```

## Webhook

AsiaPay POSTs JSON to your `notify_url`. Verify the JWT `sign` before updating orders.

```php
public function webhook(Request $request)
{
    if (! AsiapayPayment::verifyWebhook($request->all())) {
        return response()->json(['message' => 'Invalid signature'], 400);
    }

    $notification = AsiapayPayment::parseWebhook($request->all());

    // Confirm with queryOrder when amount or status matters for fulfillment.
    return response(AsiapayPayment::webhookAckBody(), 200);
}
```

Confirm the exact acknowledgment body with `devops@asiapay.iq` if `SUCCESS` is rejected in your environment.

## Checking status

```php
$status = AsiapayPayment::queryOrder('ORDER-123');

if ($status->isSuccessful()) {
    // order_status PAY_SUCCESS
}
```

## Refunds

```php
AsiapayPayment::refund(
    merchOrderId: 'ORDER-123',
    refundRequestNo: 'REF-'.time(),
    refundReason: 'Customer request',
);
```

## Missed notifications: sync command

```bash
php artisan asiapay:sync-statuses
```

Re-polls `payment.queryorder` for rows that are not in a terminal `order_status`.

## Multiple merchants

Add keys under `merchants` in `config/asiapay.php` and pass the merchant name as the last argument to SDK methods.

## Automatic persistence

Migrations create `asiapay_payments` and `asiapay_refunds`. Listeners update rows on `PaymentCreated`, `PaymentStatusChecked`, `PaymentRefunded`, and `WebhookReceived`.

## Events

- `PaymentCreated`
- `PaymentStatusChecked`
- `PaymentRefunded`
- `WebhookReceived`

## API mapping

| SDK method | AsiaPay `method` | HTTP path |
|------------|------------------|-----------|
| (token) | — | `POST .../payment/v1/token` |
| `createOrder` | `payment.preorder` | `POST .../merchant/preOrder` |
| `queryOrder` | `payment.queryorder` | `POST .../merchant/queryOrder` |
| `refund` | `payment.refund` | `POST .../merchant/refund` |

| Field (query) | Example |
|---------------|---------|
| `order_status` | `PAY_SUCCESS` |
| Webhook `trade_status` | `Completed` |
| Refund `refund_status` | `REFUND_SUCCESS` |

## Security

- Verify every webhook signature (`verifyWebhook` / `parseWebhook`).
- Match `merch_order_id`, amount, and currency to your order before fulfillment.
- Treat redirect URLs as UX only — use `queryOrder` as the source of truth.

## Testing

```bash
composer test
```

## Full example

See [docs/examples/PaymentController.php](docs/examples/PaymentController.php).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT — see [LICENSE.md](LICENSE.md).
