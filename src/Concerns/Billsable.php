<?php

namespace OkekeDev\Bachs\Concerns;

use Illuminate\Database\Eloquent\Model;
use OkekeDev\Bachs\Dto\CheckoutSession;
use OkekeDev\Bachs\Dto\Customer;
use OkekeDev\Bachs\Dto\Subscription;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Resources\CheckoutSessions;
use OkekeDev\Bachs\Resources\Customers;
use OkekeDev\Bachs\Resources\Subscriptions;

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
     * Get the column name used to store the Bachs subscription ID.
     */
    public static function getBachsSubscriptionIdColumn(): string
    {
        return static::$bachsSubscriptionIdColumn ?? 'bachs_subscription_id';
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
     * Get the Bachs subscription ID stored on this model.
     */
    public function bachsSubscriptionId(): ?string
    {
        $column = static::getBachsSubscriptionIdColumn();

        return $this->getAttribute($column);
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
    public function checkout(array $params = []): CheckoutSession
    {
        $customerId = $this->bachsCustomerId();

        if ($customerId !== null) {
            $params['customer'] = ['customer_id' => $customerId];
        } else {
            $params['customer'] = array_merge([
                'email' => $this->getEmailForBachs(),
            ], isset($params['customer']) ? [] : ['name' => $this->getNameForBachs()]);
        }

        return CheckoutSessions::create($params);
    }

    /**
     * Subscribe this customer to a product by creating a checkout session.
     *
     * Subscriptions in Bachs are created by completing a checkout for a
     * recurring product. This method creates a checkout session with the
     * specified product.
     *
     * @param  array<string, mixed>  $params
     */
    public function subscribeTo(string $productId, array $params = []): CheckoutSession
    {
        $params['product_cart'] = array_merge(
            [['product_id' => $productId, 'quantity' => 1]],
            $params['product_cart'] ?? []
        );

        return $this->checkout($params);
    }

    /**
     * Get the active subscription for this customer.
     *
     * Returns null if no subscription ID is stored or if the subscription
     * cannot be retrieved.
     */
    public function subscription(): ?Subscription
    {
        $subscriptionId = $this->bachsSubscriptionId();

        if ($subscriptionId === null) {
            return null;
        }

        try {
            return Subscriptions::get($subscriptionId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Determine if this customer has an active subscription.
     */
    public function subscribed(): bool
    {
        $subscription = $this->subscription();

        return $subscription !== null && ($subscription->isActive() || $subscription->isTrialing());
    }

    /**
     * Cancel the active subscription for this customer.
     */
    public function cancel(): ?Subscription
    {
        $subscriptionId = $this->bachsSubscriptionId();

        if ($subscriptionId === null) {
            throw new BachsInvalidArgumentException(
                'This model does not have an active subscription to cancel.'
            );
        }

        return Subscriptions::cancel($subscriptionId);
    }

    /**
     * Resume a canceled subscription for this customer.
     *
     * This updates the subscription to cancel at the end of the current
     * period instead of immediately.
     */
    public function resume(): ?Subscription
    {
        $subscriptionId = $this->bachsSubscriptionId();

        if ($subscriptionId === null) {
            throw new BachsInvalidArgumentException(
                'This model does not have a subscription to resume.'
            );
        }

        return Subscriptions::update($subscriptionId, ['cancel_at_period_end' => false]);
    }
}
