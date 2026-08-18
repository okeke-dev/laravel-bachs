<?php

namespace OkekeDev\Bachs\Dto;

/**
 * A Bachs refund.
 */
final class Refund extends Dto
{
    public function id(): string
    {
        return $this->str('refund_id') ?? $this->str('id') ?? '';
    }

    public function chargeId(): ?string
    {
        return $this->str('charge_id');
    }

    public function reference(): ?string
    {
        return $this->str('reference');
    }

    public function status(): string
    {
        return $this->str('status') ?? 'processing';
    }

    public function isProcessing(): bool
    {
        return $this->status() === 'processing';
    }

    public function isSuccess(): bool
    {
        return $this->status() === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status() === 'failed';
    }

    public function requestedAmount(): ?string
    {
        return $this->str('requested_amount');
    }

    public function refundedAmount(): ?string
    {
        return $this->str('refunded_amount');
    }

    public function refundFeeAmount(): ?string
    {
        return $this->str('refund_fee_amount');
    }

    public function feeBearer(): ?string
    {
        return $this->str('fee_bearer');
    }

    public function reason(): ?string
    {
        return $this->str('reason');
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
