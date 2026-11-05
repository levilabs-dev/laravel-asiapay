<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LeviLabs\LaravelAsiapay\Enums\OrderStatus;
use LeviLabs\LaravelAsiapay\Enums\TradeStatus;

/**
 * @property string $merchant
 * @property string $merch_order_id
 * @property string|null $prepay_id
 * @property string|null $payment_order_id
 * @property string|null $trans_id
 * @property OrderStatus $order_status
 * @property TradeStatus|null $trade_status
 * @property float $amount
 * @property string $currency
 * @property string|null $title
 * @property string|null $redirect_url
 * @property array<string, mixed>|null $meta
 * @property CarbonImmutable|null $checked_at
 */
class AsiapayPayment extends Model
{
    protected $table = 'asiapay_payments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_status' => OrderStatus::class,
            'trade_status' => TradeStatus::class,
            'amount' => 'decimal:3',
            'meta' => 'array',
            'checked_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<AsiapayRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(AsiapayRefund::class, 'asiapay_payment_id');
    }
}
