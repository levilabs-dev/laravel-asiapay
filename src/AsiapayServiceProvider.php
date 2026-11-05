<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay;

use Illuminate\Support\Facades\Event;
use LeviLabs\LaravelAsiapay\Console\Commands\SyncAsiapayStatuses;
use LeviLabs\LaravelAsiapay\Contracts\AsiapayPaymentServiceContract;
use LeviLabs\LaravelAsiapay\Events\PaymentCreated;
use LeviLabs\LaravelAsiapay\Events\PaymentRefunded;
use LeviLabs\LaravelAsiapay\Events\PaymentStatusChecked;
use LeviLabs\LaravelAsiapay\Events\WebhookReceived;
use LeviLabs\LaravelAsiapay\Listeners\PersistAsiapayPayment;
use LeviLabs\LaravelAsiapay\Services\AsiapayPaymentService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AsiapayServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('asiapay')
            ->hasConfigFile('asiapay')
            ->hasMigrations('create_asiapay_payments_table', 'create_asiapay_refunds_table')
            ->runsMigrations()
            ->hasCommand(SyncAsiapayStatuses::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(AsiapayPaymentServiceContract::class, AsiapayPaymentService::class);
    }

    public function packageBooted(): void
    {
        $listener = PersistAsiapayPayment::class;

        Event::listen(PaymentCreated::class, [$listener, 'onCreated']);
        Event::listen(PaymentStatusChecked::class, [$listener, 'onStatusChecked']);
        Event::listen(PaymentRefunded::class, [$listener, 'onRefunded']);
        Event::listen(WebhookReceived::class, [$listener, 'onWebhook']);
    }
}
