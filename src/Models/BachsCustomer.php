<?php

namespace OkekeDev\Bachs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $bachs_id
 * @property string $email
 * @property string|null $name
 * @property string|null $phone_number
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $billing_address
 * @property string|null $bachs_created_at
 * @property string|null $bachs_updated_at
 * @property string $created_at
 * @property string $updated_at
 */
class BachsCustomer extends Model
{
    protected $table = 'bachs_customers';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'billing_address' => 'array',
    ];

    public function getTable(): string
    {
        return config('bachs.database.tables.customers', 'bachs_customers');
    }

    /** @return HasMany<BachsSubscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(BachsSubscription::class, 'customer_id', 'bachs_id');
    }

    /** @return HasMany<BachsPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(BachsPayment::class, 'subscription_id')
            ->whereIn('subscription_id', function ($query) {
                $query->select('bachs_id')
                    ->from(config('bachs.database.tables.subscriptions', 'bachs_subscriptions'))
                    ->where('customer_id', $this->bachs_id);
            });
    }
}
