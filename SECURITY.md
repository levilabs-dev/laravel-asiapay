# Security Policy

## Supported versions

Security fixes are applied to the latest release on the `main` branch.

## Reporting a vulnerability

Email **hello@levilabs.dev** with details. Do not open public issues for undisclosed security problems.

## Integration security

- This package targets **AsiaPay Iraq** ([asiapay.iq](https://www.asiapay.iq/)) only — not [asiapay.com](https://www.asiapay.com/) (Hong Kong / PayDollar).
- Never mark an order paid from a webhook body alone: verify the JWT `sign`, match amount and currency, then confirm with `queryOrder` when in doubt.
- Store `ASIAPAY_APP_SECRET`, `ASIAPAY_PRIVATE_KEY`, and related credentials in environment variables, not in source control.
