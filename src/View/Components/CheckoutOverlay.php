<?php

namespace OkekeDev\Bachs\View\Components;

use Illuminate\View\Component;
use OkekeDev\Bachs\Resources\CheckoutSessions;

class CheckoutOverlay extends Component
{
    public function __construct(
        public ?string $product = null,
        public ?string $customer = null,
        public ?string $email = null,
        public ?string $successUrl = null,
        public ?string $cancelUrl = null,
        public ?string $name = null,
        public ?string $class = null,
    ) {}

    /**
     * Create the checkout session and get the URL.
     */
    public function checkoutUrl(): string
    {
        $params = $this->buildParams();

        $session = CheckoutSessions::create($params);

        return $session->url();
    }

    /**
     * Build the checkout session parameters.
     *
     * @return array<string, mixed>
     */
    protected function buildParams(): array
    {
        $params = [];

        if ($this->product !== null) {
            $params['product_cart'] = [
                ['product_id' => $this->product, 'quantity' => 1],
            ];
        }

        if ($this->customer !== null) {
            $params['customer'] = ['customer_id' => $this->customer];
        } elseif ($this->email !== null) {
            $params['customer'] = ['email' => $this->email];
        }

        if ($this->successUrl !== null) {
            $params['success_url'] = $this->successUrl;
        }

        if ($this->cancelUrl !== null) {
            $params['cancel_url'] = $this->cancelUrl;
        }

        return $params;
    }

    public function render()
    {
        return view('bachs::components.checkout-overlay');
    }
}
