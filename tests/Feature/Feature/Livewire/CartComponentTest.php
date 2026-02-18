<?php

declare(strict_types=1);

use App\Livewire\CartComponent;
use Livewire\Livewire;

test('mounting CartComponent with an empty cart does not throw', function (): void {
    $component = Livewire::test(CartComponent::class);

    $component->assertHasNoErrors();
    expect($component->get('totalAmount'))->toEqual(0)
        ->and($component->get('subtotalAmount'))->toEqual(0);
});

test('totalAmount and subtotalAmount are zero for an empty cart', function (): void {
    $component = Livewire::test(CartComponent::class);

    expect($component->get('totalAmount'))->toEqual(0)
        ->and($component->get('subtotalAmount'))->toEqual(0)
        ->and($component->get('discountAmount'))->toEqual(0);
});

test('dispatching currencyChanged does not throw on empty cart', function (): void {
    $component = Livewire::test(CartComponent::class);

    $component->dispatch('currencyChanged', 'USD');

    $component->assertHasNoErrors();
    expect($component->get('totalAmount'))->toEqual(0);
});
