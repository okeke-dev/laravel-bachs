<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\ValueObjects\Cadence;
use OkekeDev\Bachs\ValueObjects\Money;

/**
 * A Bachs product.
 */
final class Product extends Dto
{
    public function id(): string
    {
        return $this->str('id') ?? '';
    }

    public function organizationId(): string
    {
        return $this->str('organization_id') ?? '';
    }

    public function name(): string
    {
        return $this->str('name') ?? '';
    }

    public function description(): ?string
    {
        return $this->str('description');
    }

    /**
     * The product's primary price, in its default currency.
     */
    public function price(): Price
    {
        return Price::from($this->arr('price'));
    }

    /**
     * `active` or `archived`.
     */
    public function status(): string
    {
        return $this->str('status') ?? 'active';
    }

    public function isActive(): bool
    {
        return $this->status() === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status() === 'archived';
    }

    public function isRecurring(): bool
    {
        return $this->billingCycle() !== null;
    }

    /**
     * Custom key-value data attached to the product, or `null` when unset.
     *
     * @return array<mixed>|null
     */
    public function metadata(): ?array
    {
        $metadata = $this->data['metadata'] ?? null;

        return is_array($metadata) ? $metadata : null;
    }

    /**
     * Media items (images) attached to the product.
     *
     * @return list<MediaItem>
     */
    public function media(): array
    {
        return array_map(fn (array $item) => MediaItem::from($item), $this->arr('media'));
    }

    public function actorId(): ?string
    {
        return $this->str('actor_id');
    }

    public function createdAt(): ?string
    {
        return $this->str('created_at');
    }

    public function updatedAt(): ?string
    {
        return $this->str('updated_at');
    }

    public function archivedAt(): ?string
    {
        return $this->str('archived_at');
    }

    public function billingCycle(): ?Cadence
    {
        return Cadence::fromArray($this->arr('billing_cycle'));
    }

    public function trialPeriod(): ?Cadence
    {
        return Cadence::fromArray($this->arr('trial_period'));
    }

    /**
     * All prices configured on the product, one per currency.
     *
     * @return list<Price>
     */
    public function prices(): array
    {
        return array_map(fn (array $price) => Price::from($price), $this->arr('prices'));
    }

    public function totalPayments(): int
    {
        return $this->int('total_payments') ?? 0;
    }

    /**
     * Running total collected for this product, in the product currency.
     */
    public function totalAmount(): ?Money
    {
        $amount = $this->str('total_amount');

        return $amount === null ? null : Money::fromDecimal($amount, $this->price()->currency());
    }
}
