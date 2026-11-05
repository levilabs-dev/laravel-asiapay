<?php

declare(strict_types=1);

namespace LeviLabs\LaravelAsiapay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LeviLabs\LaravelAsiapay\Enums\RefundStatus;

/**
 * @property int $asiapay_payment_id
 * @property string $refund_request_no
 * @property string|null $refund_order_id
 * @property RefundStatus $status
 * @property float|null $amount
 * @property string $currency
 * @property string|null $reason
 * @property array<string, mixed>|null $meta
 */
class AsiapayRefund extends Model
{
    protected $table = 'asiapay_refunds';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount' => 'decimal:3',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AsiapayPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(AsiapayPayment::class, 'asiapay_payment_id');
    }
}
