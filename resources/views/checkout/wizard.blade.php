<x-admin-layout>
    @section('page-title')
        Checkout
    @endsection

    @section('styles')
        <link rel="stylesheet" href="{{ asset('css/checkout.css') }}">
    @endsection

    <div class="container-fluid">
        @livewire('checkout.checkout-wizard')
    </div>
</x-admin-layout>

