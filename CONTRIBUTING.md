# Contributing

Contributions are welcome. Please open an issue before large changes.

## Development

```bash
composer install
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

## Scope

This package implements the merchant API documented at [asiapay.iq/integration](https://www.asiapay.iq/integration). Use that guide and credentials from AsiaPay as the source of truth for signing and endpoints.
