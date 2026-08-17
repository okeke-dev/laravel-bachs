<?php

namespace OkekeDev\Bachs\Dto;

/**
 * A Bachs subscription.
 */
final class Subscription extends Dto
{
    public function id(): string
    {
        return $this->str('id') ?? '';
    }

    public function customerId(): string
    {
        return $this->str('customer_id') ?? $this->arr('customer')['customer_id'] ?? '';
    }

    public function paymentMethodId(): ?string
    {
        return $this->str('payment_method_id');
    }

    public function status(): string
    {
        return $this->str('status') ?? 'active';
    }

    public function isActive(): bool
    {
        return $this->status() === 'active';
    }

    public function isTrialing(): bool
    {
        return $this->status() === 'trialing';
    }

    public function isPastDue(): bool
    {
        return $this->status() === 'past_due';
    }

    public function isCanceled(): bool
    {
        return $this->status() === 'canceled';
    }

    public function isPaused(): bool
    {
        return $this->status() === 'paused';
    }

    public function collectionMethod(): ?string
    {
        return $this->str('collection_method');
    }

    public function currency(): ?string
    {
        return $this->str('currency');
    }

    public function amount(): ?string
    {
        return $this->str('amount');
    }

    public function quantity(): int
    {
        return $this->int('quantity') ?? 1;
    }

    /**
     * @return array{interval: string, frequency: int}|null
     */
    public function billingCycle(): ?array
    {
        $cycle = $this->arr('billing_cycle');

        return $cycle !== [] ? $cycle : null;
    }

    public function currentPeriodStart(): ?string
    {
        return $this->str('current_period_start');
    }

    public function currentPeriodEnd(): ?string
    {
        return $this->str('current_period_end');
    }

    public function nextBilledAt(): ?string
    {
        return $this->str('next_billed_at');
    }

    public function trialEnd(): ?string
    {
        return $this->str('trial_end');
    }

    public function cancelAtPeriodEnd(): bool
    {
        return $this->bool('cancel_at_period_end');
    }

    public function canceledAt(): ?string
    {
        return $this->str('canceled_at');
    }

    public function productId(): ?string
    {
        return $this->arr('product')['id'] ?? $this->str('product_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        return $this->arr('items');
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->arr('metadata');
    }

    public function createdAt(): ?string
    {
        return $this->str('created_at');
    }

    public function updatedAt(): ?string
    {
        return $this->str('updated_at');
    }
}
