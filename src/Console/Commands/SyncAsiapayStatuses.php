<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Console\Commands;

use Illuminate\Console\Command;
use LeviLabs\LaravelAsiapay\Contracts\AsiapayPaymentServiceContract;
use LeviLabs\LaravelAsiapay\Enums\OrderStatus;
use LeviLabs\LaravelAsiapay\Models\AsiapayPayment;
use Throwable;

class SyncAsiapayStatuses extends Command
{
    protected $signature = 'asiapay:sync-statuses {--limit=100}';

    protected $description = 'Re-check non-terminal AsiaPay orders via payment.queryorder';

    public function handle(AsiapayPaymentServiceContract $payments): int
    {
        $limit = (int) $this->option('limit');

        $terminalStatuses = array_map(
            fn (OrderStatus $status): string => $status->value,
            array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isTerminal()),
        );

        $pending = AsiapayPayment::query()
            ->whereNotIn('order_status', $terminalStatuses)
            ->limit($limit)
            ->get();

        foreach ($pending as $payment) {
            try {
                $payments->queryOrder($payment->merch_order_id, $payment->merchant);
            } catch (Throwable $e) {
                $this->warn("Order {$payment->merch_order_id} could not be re-checked: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$pending->count()} non-terminal order(s).");

        return self::SUCCESS;
    }
}
