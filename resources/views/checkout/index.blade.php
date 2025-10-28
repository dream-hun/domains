<x-admin-layout>
    @section('page-title')
        Checkout
    @endsection

    @section('styles')
        <link href="{{ asset('css/checkout.css') }}" rel="stylesheet"/>
    @endsection

    <livewire:checkout.checkout-wizard />
</x-admin-layout>
