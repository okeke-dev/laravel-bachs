<?php

namespace OkekeDev\Bachs\Dto;

/**
 * A supported payment rail for a payment method + currency combination.
 */
final class PaymentRailOption extends Dto
{
    public function id(): string
    {
        return $this->str('id') ?? '';
    }

    public function name(): ?string
    {
        return $this->str('name');
    }

    public function active(): ?bool
    {
        $active = $this->data['active'] ?? null;

        return $active === null ? null : (bool) $active;
    }
}
