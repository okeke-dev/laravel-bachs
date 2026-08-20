@props(['product' => null, 'customer' => null, 'email' => null, 'successUrl' => null, 'cancelUrl' => null, 'name' => null, 'class' => null])

@php
    $checkoutUrl = app(OkekeDev\Bachs\View\Components\Subscribe::class, [
        'product' => $product,
        'customer' => $customer,
        'email' => $email,
        'successUrl' => $successUrl,
        'cancelUrl' => $cancelUrl,
    ])->checkoutUrl();
@endphp

<a
    href="{{ $checkoutUrl }}"
    {{ $attributes->class(['bachs-checkout' => true, 'bachs-subscribe' => true])->merge(['class' => $class]) }}
    data-bachs-subscribe
    role="button"
    aria-label="{{ $name ?? 'Subscribe' }}"
>
    {{ $slot ?: ($name ?? 'Subscribe') }}
</a>
