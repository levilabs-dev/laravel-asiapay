# Changelog

All notable changes to `levilabs/laravel-asiapay` will be documented in this file.

## Unreleased

- Bump `firebase/php-jwt` to `^7.2` (security hardening; HS256 usage unchanged).

## 1.0.0

- Initial release: AsiaPay Iraq (`asiapay.iq`) merchant gateway — token auth, JWT-signed `preOrder` / `queryOrder` / `refund`, webhook verification, persistence, and `asiapay:sync-statuses`.
