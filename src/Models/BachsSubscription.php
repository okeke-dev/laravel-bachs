<?php

namespace OkekeDev\Bachs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $bachs_id
 * @property string|null $customer_id
 * @property string|null $payment_method_id
 * @property string $status
 * @property string|null $collection_method
 * @property string|null $currency
 * @property string|null $amount
 * @property int $quantity
 * @property array<string, mixed>|null $billing_cycle
 * @property string|null $current_period_start
 * @property string|null $current_period_end
 * @property string|null $next_billed_at
 * @property string|null $trial_end
 * @property bool $cancel_at_period_end
 * @property string|null $canceled_at
 * @property string|null $product_id
 * @property array<string, mixed>|null $items
 * @property array<string, mixed>|null $metadata
 * @property string|null $bachs_created_at
 * @property string|null $bachs_updated_at
 * @property string $created_at
 * @property string $updated_at
 */
class BachsSubscription extends Model
{
    protected $table = 'bachs_subscriptions';

    protected $guarded = [];

    protected $casts = [
        'billing_cycle' => 'array',
        'items' => 'array',
        'metadata' => 'array',
        'cancel_at_period_end' => 'boolean',
        'quantity' => 'integer',
    ];

    public function getTable(): string
    {
        return config('bachs.database.tables.subscriptions', 'bachs_subscriptions');
    }

    /** @return HasMany<BachsPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(BachsPayment::class, 'subscription_id', 'bachs_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }
}
