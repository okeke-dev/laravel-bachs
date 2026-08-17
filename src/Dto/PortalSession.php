<?php

namespace OkekeDev\Bachs\Dto;

/**
 * A Bachs customer portal session.
 */
final class PortalSession extends Dto
{
    public function id(): string
    {
        return $this->str('session_id') ?? $this->str('id') ?? '';
    }

    public function customerId(): string
    {
        return $this->str('customer_id') ?? '';
    }

    public function url(): string
    {
        return $this->str('url') ?? '';
    }

    public function status(): string
    {
        return $this->str('status') ?? 'active';
    }

    public function isActive(): bool
    {
        return $this->status() === 'active';
    }

    public function expiresAt(): ?string
    {
        return $this->str('expires_at');
    }

    public function createdAt(): ?string
    {
        return $this->str('created_at');
    }
}
