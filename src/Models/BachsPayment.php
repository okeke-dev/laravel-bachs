<?php

namespace OkekeDev\Bachs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $bachs_id
 * @property string|null $charge_id
 * @property string|null $reference
 * @property string|null $billing_reason
 * @property string|null $checkout_id
 * @property string $status
 * @property string|null $amount
 * @property string|null $amount_paid
 * @property string|null $amount_remaining
 * @property string|null $currency
 * @property string|null $fee_usd
 * @property string|null $payment_method
 * @property string|null $subscription_id
 * @property array<string, mixed>|null $line_items
 * @property array<string, mixed>|null $refunds
 * @property array<string, mixed>|null $status_history
 * @property array<string, mixed>|null $metadata
 * @property string|null $bachs_created_at
 * @property string|null $bachs_updated_at
 * @property string $created_at
 * @property string $updated_at
 */
class BachsPayment extends Model
{
    protected $table = 'bachs_payments';

    protected $guarded = [];

    protected $casts = [
        'line_items' => 'array',
        'refunds' => 'array',
        'status_history' => 'array',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('bachs.database.tables.payments', 'bachs_payments');
    }

    /** @return BelongsTo<BachsSubscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(BachsSubscription::class, 'subscription_id', 'bachs_id');
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
