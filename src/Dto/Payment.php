<?php

namespace OkekeDev\Bachs\Dto;

use OkekeDev\Bachs\Resources\Refunds;

/**
 * A Bachs payment.
 */
final class Payment extends Dto
{
    public function id(): string
    {
        return $this->str('payment_id') ?? $this->str('id') ?? '';
    }

    public function chargeId(): ?string
    {
        return $this->str('charge_id');
    }

    public function reference(): ?string
    {
        return $this->str('reference');
    }

    public function billingReason(): ?string
    {
        return $this->str('billing_reason');
    }

    public function checkoutId(): ?string
    {
        return $this->str('checkout_id');
    }

    public function status(): string
    {
        return $this->str('status') ?? 'created';
    }

    public function isSucceeded(): bool
    {
        return $this->status() === 'succeeded';
    }

    public function isProcessing(): bool
    {
        return $this->status() === 'processing';
    }

    public function isFailed(): bool
    {
        return $this->status() === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status() === 'refunded';
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->status() === 'partially_refunded';
    }

    public function isExpired(): bool
    {
        return $this->status() === 'expired';
    }

    public function isCancelled(): bool
    {
        return $this->status() === 'cancelled';
    }

    public function isRefundable(): bool
    {
        return $this->bool('is_refundable');
    }

    public function amount(): ?string
    {
        return $this->str('amount');
    }

    public function amountPaid(): ?string
    {
        return $this->str('amount_paid');
    }

    public function amountRemaining(): ?string
    {
        return $this->str('amount_remaining');
    }

    public function currency(): ?string
    {
        return $this->str('currency');
    }

    public function feeUsd(): ?string
    {
        return $this->str('fee_usd');
    }

    public function paymentMethod(): ?string
    {
        return $this->str('payment_method');
    }

    public function subscriptionId(): ?string
    {
        return $this->str('subscription_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lineItems(): array
    {
        return $this->arr('line_items');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function refunds(): array
    {
        return $this->arr('refunds');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function statusHistory(): array
    {
        return $this->arr('status_history');
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

    /**
     * Create a refund for this payment.
     *
     * @param  array<string, mixed>  $params
     */
    public function refund(array $params = []): Refund
    {
        return Refunds::create(array_merge([
            'charge_id' => $this->chargeId(),
        ], $params));
    }
}
