<?php

namespace OkekeDev\Bachs\Webhooks;

/**
 * A parsed Bachs webhook event envelope.
 */
class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected readonly string $id,
        protected readonly string $type,
        protected readonly string $createdAt,
        protected readonly string $organizationId,
        protected readonly array $data,
        protected readonly ?string $account = null,
    ) {}

    /**
     * Create a WebhookEvent from the raw payload array.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            id: $payload['id'] ?? '',
            type: $payload['type'] ?? '',
            createdAt: $payload['created_at'] ?? '',
            organizationId: $payload['organization_id'] ?? '',
            data: $payload['data'] ?? [],
            account: $payload['account'] ?? null,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function organizationId(): string
    {
        return $this->organizationId;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function account(): ?string
    {
        return $this->account;
    }

    /**
     * Determine if this is a Connect event (has an account field).
     */
    public function isConnectEvent(): bool
    {
        return $this->account !== null;
    }

    /**
     * Get the event category based on the type prefix.
     */
    public function category(): string
    {
        $parts = explode('.', $this->type);

        return $parts[0];
    }
}
