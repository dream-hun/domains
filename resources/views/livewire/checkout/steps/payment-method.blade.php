<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="mb-0">Payment Method</h4>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">Select your preferred payment method:</p>

        @if(empty($paymentMethods))
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                No payment methods are currently available. Please contact support.
            </div>
        @else
            <div class="row g-3">
                @foreach($paymentMethods as $method)
                    <div class="col-md-4 col-sm-6">
                        <div class="payment-method-option h-100"
                             wire:click="selectPaymentMethod('{{ $method['id'] }}')"
                             role="button"
                             tabindex="0"
                             aria-label="Select payment method {{ $method['name'] }}"
                             style="cursor: pointer;">
                            <div class="card h-100 {{ $selectedPaymentMethod === $method['id'] ? 'border-primary shadow-sm' : 'border' }}"
                                 style="transition: all 0.2s;">
                                <div class="card-body d-flex align-items-center justify-content-center"
                                     style="min-height: 100px;">
                                    <input type="radio"
                                           id="payment_{{ $method['id'] }}"
                                           name="payment_method"
                                           class="position-absolute"
                                           style="opacity: 0; pointer-events: none;"
                                           {{ $selectedPaymentMethod === $method['id'] ? 'checked' : '' }}
                                           wire:model="selectedPaymentMethod"
                                           value="{{ $method['id'] }}">
                                    @if($method['id'] === 'stripe')
                                        <img src="{{ asset('Stripe_Logo,_revised_2016.svg.png') }}"
                                             alt="Stripe"
                                             class="img-fluid"
                                             style="max-height: 50px; max-width: 150px;">
                                    @elseif($method['id'] === 'paypal')
                                        <i class="fab fa-paypal fa-3x text-primary"></i>
                                    @elseif($method['id'] === 'kpay')
                                        <img src="{{ asset('MomoAirtel.png') }}"
                                             alt="Momo Airtel"
                                             class="img-fluid"
                                             style="max-height: 50px; max-width: 150px;">
                                    @elseif($method['id'] === 'account_credit')
                                        <i class="fas fa-wallet fa-3x text-success"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <div class="card-footer">
        <button wire:click="previousStep" class="btn btn-secondary">
            <i class="bi bi-arrow-left mr-2"></i>
            Back
        </button>
        @if(!empty($paymentMethods))
            <button wire:click="completeOrder"
                    class="btn btn-success btn-lg float-right"
                    {{ $isProcessing ? 'disabled' : '' }}>
                @if($isProcessing)
                    <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
                    Processing...
                @else
                    <i class="bi bi-lock mr-2"></i>
                    Complete Order
                @endif
            </button>
        @endif
    </div>
</div>
