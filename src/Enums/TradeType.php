<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Enums;

/**
 * trade_type on payment.preorder — see https://www.asiapay.iq/integration
 */
enum TradeType: string
{
    case Checkout = 'Checkout';

    case CrossApp = 'Cross-App';

    case Pwa = 'PWA';
}
