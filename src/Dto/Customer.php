<?php

namespace OkekeDev\Bachs\Dto;

/**
 * A Bachs customer.
 */
final class Customer extends Dto
{
    public function id(): string
    {
        return $this->str('customer_id') ?? $this->str('id') ?? '';
    }

    public function email(): string
    {
        return $this->str('email') ?? '';
    }

    public function name(): ?string
    {
        return $this->str('name');
    }

    public function phoneNumber(): ?string
    {
        return $this->str('phone_number');
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->arr('metadata');
    }

    /**
     * @return array<string, mixed>
     */
    public function billingAddress(): array
    {
        return $this->arr('billing_address');
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
