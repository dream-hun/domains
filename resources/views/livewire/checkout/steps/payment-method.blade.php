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
            <div class="payment-methods">
                @foreach($paymentMethods as $method)
                    <div class="payment-method-option mb-3"
                         wire:click="selectPaymentMethod('{{ $method['id'] }}')"
                         role="button"
                         tabindex="0"
                         aria-label="Select payment method {{ $method['name'] }}"
                         style="cursor: pointer;">
                        <div class="card {{ $selectedPaymentMethod === $method['id'] ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <div class="custom-control custom-radio">
                                    <input type="radio"
                                           id="payment_{{ $method['id'] }}"
                                           name="payment_method"
                                           class="custom-control-input"
                                           {{ $selectedPaymentMethod === $method['id'] ? 'checked' : '' }}
                                           wire:model="selectedPaymentMethod"
                                           value="{{ $method['id'] }}">
                                    <label class="custom-control-label" for="payment_{{ $method['id'] }}">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <div>
                                                <strong>{{ $method['name'] }}</strong>
                                                @if(isset($method['balance']))
                                                    <br>
                                                    <small class="text-muted">Balance: {{ $method['balance'] }}</small>
                                                @endif
                                            </div>
                                            @if($method['id'] === 'stripe')
                                                <i class="fab fa-cc-stripe fa-2x text-primary"></i>
                                            @elseif($method['id'] === 'paypal')
                                                <i class="fab fa-paypal fa-2x text-primary"></i>
                                            @elseif($method['id'] === 'kpay')
                                                <i class="bi bi-phone fa-2x text-success"></i>
                                            @elseif($method['id'] === 'account_credit')
                                                <i class="fas fa-wallet fa-2x text-success"></i>
                                            @endif
                                        </div>
                                    </label>
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
