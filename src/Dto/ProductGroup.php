<?php

namespace OkekeDev\Bachs\Dto;

/**
 * A Bachs product group: a named collection of products.
 */
final class ProductGroup extends Dto
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

    /**
     * @return list<Product>
     */
    public function products(): array
    {
        return array_map(fn (array $product) => Product::from($product), $this->arr('products'));
    }

    public function productCount(): int
    {
        return count($this->products());
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
