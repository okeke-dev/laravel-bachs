@props(['product' => null, 'customer' => null, 'email' => null, 'successUrl' => null, 'cancelUrl' => null, 'name' => null, 'class' => null, 'id' => null])

@php
    $componentId = $id ?? 'bachs-overlay-' . md5($product . $customer . $email);
    $checkoutUrl = app(OkekeDev\Bachs\View\Components\CheckoutOverlay::class, [
        'product' => $product,
        'customer' => $customer,
        'email' => $email,
        'successUrl' => $successUrl,
        'cancelUrl' => $cancelUrl,
    ])->checkoutUrl();
@endphp

<span
    data-bachs-overlay
    data-checkout-url="{{ $checkoutUrl }}"
    data-overlay-id="{{ $componentId }}"
    {{ $attributes->class(['bachs-checkout-overlay' => true])->merge(['class' => $class]) }}
>
    <button
        type="button"
        onclick="document.getElementById('{{ $componentId }}').style.display='flex'"
        aria-haspopup="dialog"
        aria-controls="{{ $componentId }}"
    >
        {{ $slot ?: ($name ?? 'Checkout') }}
    </button>

    <div
        id="{{ $componentId }}"
        role="dialog"
        aria-modal="true"
        aria-label="Checkout"
        style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;"
    >
        <div style="position:relative;width:100%;max-width:480px;margin:1rem;background:#fff;border-radius:8px;overflow:hidden;">
            <button
                type="button"
                onclick="this.closest('[role=dialog]').style.display='none'"
                aria-label="Close checkout"
                style="position:absolute;top:0.5rem;right:0.5rem;z-index:10;background:none;border:none;font-size:1.5rem;cursor:pointer;line-height:1;padding:0.25rem;"
            >
                &times;
            </button>
            <iframe
                src="{{ $checkoutUrl }}"
                style="width:100%;height:600px;border:none;"
                title="Bachs Checkout"
                loading="lazy"
            ></iframe>
        </div>
    </div>
</span>
