<?php

namespace OkekeDev\Bachs\Collections;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * A Laravel collection of list items, carrying the Bachs pagination metadata
 * (`next_cursor`, `prev_cursor`, `has_more`, `limit`, `offset`, `returned`,
 * `total`) alongside the items.
 *
 * @extends Collection<int, mixed>
 *
 * @phpstan-consistent-constructor
 */
class PaginatedCollection extends Collection
{
    /**
     * @param  array<mixed>  $items
     * @param  array<string, mixed>  $pagination
     */
    public function __construct(array $items = [], array $pagination = [])
    {
        parent::__construct($items);

        $this->pagination = $pagination;
    }

    /**
     * @var array<string, mixed>
     */
    protected array $pagination = [];

    /**
     * Build a collection from a Bachs list payload (`{ items, pagination }`).
     *
     * An optional callable maps each raw item (e.g. hydration into a DTO);
     * the pagination metadata is preserved.
     *
     * @param  array<mixed>  $payload
     * @param  callable(mixed): mixed|null  $map
     */
    public static function fromPayload(array $payload, ?callable $map = null): static
    {
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $pagination = is_array($payload['pagination'] ?? null) ? $payload['pagination'] : [];

        if ($map !== null) {
            $items = array_map($map, $items);
        }

        return new static($items, $pagination);
    }

    /**
     * The raw pagination metadata.
     *
     * @return array<string, mixed>
     */
    public function pagination(): array
    {
        return $this->pagination;
    }

    /**
     * Whether more results are available after the current page.
     */
    public function hasMore(): bool
    {
        return (bool) ($this->pagination['has_more'] ?? false);
    }

    /**
     * The opaque cursor for the next page, if any.
     */
    public function nextCursor(): ?string
    {
        return isset($this->pagination['next_cursor'])
            ? (string) $this->pagination['next_cursor']
            : null;
    }

    /**
     * The opaque cursor for the previous page, if any.
     */
    public function prevCursor(): ?string
    {
        return isset($this->pagination['prev_cursor'])
            ? (string) $this->pagination['prev_cursor']
            : null;
    }

    /**
     * The page size the request used.
     */
    public function limit(): ?int
    {
        return isset($this->pagination['limit']) ? (int) $this->pagination['limit'] : null;
    }

    /**
     * The offset the request started at.
     */
    public function offset(): ?int
    {
        return isset($this->pagination['offset']) ? (int) $this->pagination['offset'] : null;
    }

    /**
     * The number of items returned on this page.
     */
    public function returned(): ?int
    {
        return isset($this->pagination['returned']) ? (int) $this->pagination['returned'] : null;
    }

    /**
     * The total number of matching items across all pages, when provided.
     */
    public function total(): ?int
    {
        return isset($this->pagination['total']) ? (int) $this->pagination['total'] : null;
    }

    /**
     * Map items while preserving the pagination metadata.
     */
    public function map(callable $callback): static
    {
        return new static(Arr::map($this->items, $callback), $this->pagination);
    }
}
