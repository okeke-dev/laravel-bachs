<?php

namespace OkekeDev\Bachs\Webhooks;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Syncs webhook event data to local database models.
 *
 * When bachs.database.sync is enabled, this class extracts the relevant
 * resource from the webhook event payload and upserts it into the
 * corresponding local table. Bachs remains the source of truth; local
 * rows are read-only mirrors for query convenience.
 */
class WebhookSyncer
{
    /**
     * Sync the webhook event data to local models.
     */
    public function sync(WebhookEvent $event): void
    {
        $type = $event->type();
        $data = $event->data();

        match (true) {
            str_starts_with($type, 'customer.subscription.') => $this->syncSubscription($data),
            str_starts_with($type, 'customer.') => $this->syncCustomer($data),
            str_starts_with($type, 'collection.') => $this->syncPayment($data),
            str_starts_with($type, 'invoice.') => $this->syncPayment($data),
            str_starts_with($type, 'refund.') => $this->syncPaymentFromRefund($data),
            str_starts_with($type, 'checkout.') => $this->syncCheckout($data),
            default => null,
        };
    }

    /**
     * Sync a customer from the event data.
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncCustomer(array $data): void
    {
        $customerId = $data['customer_id'] ?? $data['id'] ?? null;

        if ($customerId === null) {
            return;
        }

        $table = Config::get('bachs.database.tables.customers', 'bachs_customers');
        $connection = Config::get('bachs.database.connection');

        DB::connection($connection)
            ->table($table)
            ->updateOrInsert(
                ['bachs_id' => $customerId],
                [
                    'email' => $data['email'] ?? '',
                    'name' => $data['name'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
                    'billing_address' => isset($data['billing_address']) ? json_encode($data['billing_address']) : null,
                    'bachs_created_at' => $data['created_at'] ?? null,
                    'bachs_updated_at' => $data['updated_at'] ?? null,
                    'updated_at' => now()->toIso8601String(),
                ]
            );
    }

    /**
     * Sync a product from the event data.
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncProduct(array $data): void
    {
        $productId = $data['product_id'] ?? $data['id'] ?? null;

        if ($productId === null) {
            return;
        }

        $table = Config::get('bachs.database.tables.products', 'bachs_products');
        $connection = Config::get('bachs.database.connection');

        DB::connection($connection)
            ->table($table)
            ->updateOrInsert(
                ['bachs_id' => $productId],
                [
                    'organization_id' => $data['organization_id'] ?? '',
                    'name' => $data['name'] ?? '',
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
                    'billing_cycle' => isset($data['billing_cycle']) ? json_encode($data['billing_cycle']) : null,
                    'trial_period' => isset($data['trial_period']) ? json_encode($data['trial_period']) : null,
                    'bachs_created_at' => $data['created_at'] ?? null,
                    'bachs_updated_at' => $data['updated_at'] ?? null,
                    'bachs_archived_at' => $data['archived_at'] ?? null,
                    'updated_at' => now()->toIso8601String(),
                ]
            );
    }

    /**
     * Sync a payment from the event data.
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncPayment(array $data): void
    {
        $paymentId = $data['payment_id'] ?? $data['id'] ?? null;

        if ($paymentId === null) {
            return;
        }

        $table = Config::get('bachs.database.tables.payments', 'bachs_payments');
        $connection = Config::get('bachs.database.connection');

        DB::connection($connection)
            ->table($table)
            ->updateOrInsert(
                ['bachs_id' => $paymentId],
                [
                    'charge_id' => $data['charge_id'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'billing_reason' => $data['billing_reason'] ?? null,
                    'checkout_id' => $data['checkout_id'] ?? null,
                    'status' => $data['status'] ?? 'created',
                    'amount' => $data['amount'] ?? null,
                    'amount_paid' => $data['amount_paid'] ?? null,
                    'amount_remaining' => $data['amount_remaining'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'fee_usd' => $data['fee_usd'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'subscription_id' => $data['subscription_id'] ?? null,
                    'line_items' => isset($data['line_items']) ? json_encode($data['line_items']) : null,
                    'refunds' => isset($data['refunds']) ? json_encode($data['refunds']) : null,
                    'status_history' => isset($data['status_history']) ? json_encode($data['status_history']) : null,
                    'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
                    'bachs_created_at' => $data['created_at'] ?? null,
                    'bachs_updated_at' => $data['updated_at'] ?? null,
                    'updated_at' => now()->toIso8601String(),
                ]
            );
    }

    /**
     * Sync a payment from refund event data (refund events nest the payment).
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncPaymentFromRefund(array $data): void
    {
        $paymentId = $data['payment_id'] ?? null;

        if ($paymentId === null) {
            return;
        }

        $this->syncPayment($data);
    }

    /**
     * Sync a subscription from the event data.
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncSubscription(array $data): void
    {
        $subscriptionId = $data['subscription_id'] ?? $data['id'] ?? null;

        if ($subscriptionId === null) {
            return;
        }

        $table = Config::get('bachs.database.tables.subscriptions', 'bachs_subscriptions');
        $connection = Config::get('bachs.database.connection');

        DB::connection($connection)
            ->table($table)
            ->updateOrInsert(
                ['bachs_id' => $subscriptionId],
                [
                    'customer_id' => $data['customer_id'] ?? null,
                    'payment_method_id' => $data['payment_method_id'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'collection_method' => $data['collection_method'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'quantity' => $data['quantity'] ?? 1,
                    'billing_cycle' => isset($data['billing_cycle']) ? json_encode($data['billing_cycle']) : null,
                    'current_period_start' => $data['current_period_start'] ?? null,
                    'current_period_end' => $data['current_period_end'] ?? null,
                    'next_billed_at' => $data['next_billed_at'] ?? null,
                    'trial_end' => $data['trial_end'] ?? null,
                    'cancel_at_period_end' => $data['cancel_at_period_end'] ?? false,
                    'canceled_at' => $data['canceled_at'] ?? null,
                    'product_id' => $data['product_id'] ?? null,
                    'items' => isset($data['items']) ? json_encode($data['items']) : null,
                    'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
                    'bachs_created_at' => $data['created_at'] ?? null,
                    'bachs_updated_at' => $data['updated_at'] ?? null,
                    'updated_at' => now()->toIso8601String(),
                ]
            );
    }

    /**
     * Sync checkout data (may contain customer and product info).
     *
     * @param  array<string, mixed>  $data
     */
    protected function syncCheckout(array $data): void
    {
        // Checkout events may contain nested customer data
        if (isset($data['customer']) && is_array($data['customer'])) {
            $this->syncCustomer($data['customer']);
        }

        // Checkout events may contain product info
        if (isset($data['product']) && is_array($data['product'])) {
            $this->syncProduct($data['product']);
        }
    }
}
