@props(['product' => null, 'customer' => null, 'email' => null, 'successUrl' => null, 'cancelUrl' => null, 'name' => null, 'class' => null])

@php
    $checkoutUrl = app(OkekeDev\Bachs\View\Components\Checkout::class, [
        'product' => $product,
        'customer' => $customer,
        'email' => $email,
        'successUrl' => $successUrl,
        'cancelUrl' => $cancelUrl,
    ])->checkoutUrl();
@endphp

<a
    href="{{ $checkoutUrl }}"
    {{ $attributes->class(['bachs-checkout' => true])->merge(['class' => $class]) }}
    data-bachs-checkout
    role="button"
    aria-label="{{ $name ?? 'Checkout' }}"
>
    {{ $slot ?: ($name ?? 'Checkout') }}
</a>
