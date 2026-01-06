<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Checkout</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Shopping Cart</a></li>
                        <li class="breadcrumb-item active">Checkout</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            {{-- Step Indicator --}}
            @include('livewire.checkout.partials.step-indicator')

            {{-- Error Message --}}
            @if($errorMessage)
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ $errorMessage }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                {{-- Main Content --}}
                <div class="col-lg-8 mb-4">
                    @if($currentStep === 1)
                        @include('livewire.checkout.steps.review-order')
                    @elseif($currentStep === 2)
                        @include('livewire.checkout.steps.contact-information')
                    @elseif($currentStep === 3)
                        @include('livewire.checkout.steps.payment-method')
                    @elseif($currentStep === 4)
                        @include('livewire.checkout.steps.confirmation')
                    @endif
                </div>

                {{-- Order Summary Sidebar --}}
                <div class="col-lg-4">
                    @include('livewire.checkout.partials.order-summary')
                </div>
            </div>

            {{-- Loading Overlay Modal --}}
            @if($isProcessing)
                <div class="modal fade show" style="display: block; z-index: 9999;" role="alert" aria-live="assertive" aria-busy="true">
                    <div class="modal-backdrop fade show" style="background-color: rgba(0, 0, 0, 0.7);"></div>
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-body text-center p-5">
                                <div class="spinner-border text-primary mb-4" style="width: 3.5rem; height: 3.5rem;" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <h4 class="mb-3 font-weight-bold">Processing Your Order</h4>
                                <p class="text-muted mb-0">Please wait while we set everything up for you. This may take a moment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Contact Creation Modal --}}
    @livewire('checkout.contact-create-modal')
</div>
