<?php

namespace OkekeDev\Bachs\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $bachs_id
 * @property string $organization_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $billing_cycle
 * @property array<string, mixed>|null $trial_period
 * @property string|null $bachs_created_at
 * @property string|null $bachs_updated_at
 * @property string|null $bachs_archived_at
 * @property string $created_at
 * @property string $updated_at
 */
class BachsProduct extends Model
{
    protected $table = 'bachs_products';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'billing_cycle' => 'array',
        'trial_period' => 'array',
    ];

    public function getTable(): string
    {
        return config('bachs.database.tables.products', 'bachs_products');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}
