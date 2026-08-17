<?php

namespace OkekeDev\Bachs\Concerns;

use Illuminate\Database\Eloquent\Model;
use OkekeDev\Bachs\Dto\Customer;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Resources\Customers;

/**
 * A concern that makes an Eloquent model billable via Bachs.
 *
 * Provides customer association, checkout helpers, subscription helpers,
 * and billing portal access. Inspired by Laravel Cashier but implemented
 * independently for Bachs.
 *
 * @method static string getBachsCustomerIdColumn()
 */
trait Billsable
{
    /**
     * Get the column name used to store the Bachs customer ID.
     */
    public static function getBachsCustomerIdColumn(): string
    {
        return static::$bachsCustomerIdColumn ?? 'bachs_customer_id';
    }

    /**
     * Get the Bachs customer ID stored on this model.
     */
    public function bachsCustomerId(): ?string
    {
        $column = static::getBachsCustomerIdColumn();

        if (! $this->hasSetMutator($column) && ! $this->hasCast($column)) {
            return $this->getAttribute($column);
        }

        return $this->{$column};
    }

    /**
     * Determine if this model has a Bachs customer associated.
     */
    public function hasBachsCustomer(): bool
    {
        return $this->bachsCustomerId() !== null;
    }

    /**
     * Retrieve the Bachs customer from the API.
     */
    public function bachsCustomer(): ?Customer
    {
        $customerId = $this->bachsCustomerId();

        if ($customerId === null) {
            return null;
        }

        return Customers::get($customerId);
    }

    /**
     * Create a new Bachs customer and store the ID on this model.
     *
     * @param  array<string, mixed>  $params
     */
    public function createAsBachsCustomer(array $params = []): Customer
    {
        if ($this->hasBachsCustomer()) {
            throw new BachsInvalidArgumentException(
                'This model already has a Bachs customer associated. Use updateBachsCustomer() instead.'
            );
        }

        $params = array_merge([
            'email' => $this->getEmailForBachs(),
            'name' => $this->getNameForBachs(),
        ], $params);

        $customer = Customers::create($params);

        $column = static::getBachsCustomerIdColumn();
        $this->setAttribute($column, $customer->id());
        $this->save();

        return $customer;
    }

    /**
     * Update the Bachs customer associated with this model.
     *
     * @param  array<string, mixed>  $params
     */
    public function updateBachsCustomer(array $params = []): Customer
    {
        $customerId = $this->bachsCustomerId();

        if ($customerId === null) {
            throw new BachsInvalidArgumentException(
                'This model does not have a Bachs customer associated. Use createAsBachsCustomer() first.'
            );
        }

        return Customers::update($customerId, $params);
    }

    /**
     * Create a billing portal session and return the URL.
     */
    public function billingPortalUrl(): string
    {
        $customerId = $this->bachsCustomerId();

        if ($customerId === null) {
            throw new BachsInvalidArgumentException(
                'This model does not have a Bachs customer associated. Create a customer first.'
            );
        }

        $session = Customers::createPortalSession($customerId);

        return $session->url();
    }

    /**
     * Get the email address to use when creating a Bachs customer.
     */
    protected function getEmailForBachs(): string
    {
        return $this->email ?? $this->getAttributes()['email'] ?? '';
    }

    /**
     * Get the name to use when creating a Bachs customer.
     */
    protected function getNameForBachs(): ?string
    {
        return $this->name ?? $this->getAttributes()['name'] ?? null;
    }

    /**
     * Begin a checkout session for this customer.
     *
     * @param  array<string, mixed>  $params
     */
    public function checkout(array $params = []): void
    {
        // Implemented in milestone 8 (Checkout).
        throw new \BadMethodCallException(
            'Checkout is not yet implemented. It will be available in milestone 8.'
        );
    }

    /**
     * Subscribe this customer to a product.
     *
     * @param  array<string, mixed>  $params
     */
    public function subscribeTo(string $productId, array $params = []): void
    {
        // Implemented in milestone 9 (Subscriptions).
        throw new \BadMethodCallException(
            'Subscriptions are not yet implemented. They will be available in milestone 9.'
        );
    }

    /**
     * Get the active subscription for this customer.
     */
    public function subscription(): ?object
    {
        // Implemented in milestone 9 (Subscriptions).
        throw new \BadMethodCallException(
            'Subscriptions are not yet implemented. They will be available in milestone 9.'
        );
    }

    /**
     * Determine if this customer has an active subscription.
     */
    public function subscribed(): bool
    {
        // Implemented in milestone 9 (Subscriptions).
        throw new \BadMethodCallException(
            'Subscriptions are not yet implemented. They will be available in milestone 9.'
        );
    }

    /**
     * Cancel the active subscription for this customer.
     */
    public function cancel(): void
    {
        // Implemented in milestone 9 (Subscriptions).
        throw new \BadMethodCallException(
            'Subscriptions are not yet implemented. They will be available in milestone 9.'
        );
    }

    /**
     * Resume a canceled subscription for this customer.
     */
    public function resume(): void
    {
        // Implemented in milestone 9 (Subscriptions).
        throw new \BadMethodCallException(
            'Subscriptions are not yet implemented. They will be available in milestone 9.'
        );
    }
}
