<?php

namespace OkekeDev\Bachs\Dto;

use Illuminate\Http\RedirectResponse;

/**
 * A Bachs checkout session.
 */
final class CheckoutSession extends Dto
{
    public function id(): string
    {
        return $this->str('checkout_id') ?? $this->str('id') ?? '';
    }

    public function url(): string
    {
        return $this->str('checkout_url') ?? $this->str('url') ?? '';
    }

    public function status(): string
    {
        return $this->str('status') ?? 'OPEN';
    }

    public function isOpen(): bool
    {
        return $this->status() === 'OPEN';
    }

    public function isComplete(): bool
    {
        return $this->status() === 'COMPLETE';
    }

    public function isExpired(): bool
    {
        return $this->status() === 'EXPIRED';
    }

    public function paymentStatus(): ?string
    {
        return $this->str('payment_status');
    }

    public function sourceType(): ?string
    {
        return $this->str('source_type');
    }

    public function amount(): ?string
    {
        return $this->str('amount');
    }

    public function currency(): ?string
    {
        return $this->str('currency');
    }

    public function customerId(): ?string
    {
        return $this->str('customer_id') ?? $this->arr('customer')['customer_id'] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function products(): array
    {
        return $this->arr('products');
    }

    public function sessionMode(): ?string
    {
        return $this->str('session_mode');
    }

    public function isCartMode(): bool
    {
        return $this->sessionMode() === 'CART';
    }

    public function isSelectionMode(): bool
    {
        return $this->sessionMode() === 'SELECTION';
    }

    public function expiresAt(): ?string
    {
        return $this->str('expires_at');
    }

    public function createdAt(): ?string
    {
        return $this->str('created_at');
    }

    /**
     * Create a redirect response to the checkout URL.
     */
    public function redirect(): RedirectResponse
    {
        return new RedirectResponse($this->url());
    }
}
