<div class="checkout-steps mb-4 py-4">
    <div class="row">
        <div class="col-3">
            <div class="step {{ $currentStep >= 1 ? 'active' : '' }} {{ $currentStep > 1 ? 'completed' : '' }}">
                <div class="step-circle">
                    @if($currentStep > 1)
                        <i class="bi bi-check2-circle"></i>
                    @else
                        <span class="step-number">1</span>
                    @endif
                </div>
                <div class="step-label">Review Order</div>
            </div>
        </div>
        <div class="col-3">
            <div class="step {{ $currentStep >= 2 ? 'active' : '' }} {{ $currentStep > 2 ? 'completed' : '' }}">
                <div class="step-circle">
                    @if($currentStep > 2)
                        <i class="bi bi-check2-circle"></i>
                    @else
                        <span class="step-number">2</span>
                    @endif
                </div>
                <div class="step-label">Contact Information</div>
            </div>
        </div>
        <div class="col-3">
            <div class="step {{ $currentStep >= 3 ? 'active' : '' }} {{ $currentStep > 3 ? 'completed' : '' }}">
                <div class="step-circle">
                    @if($currentStep > 3)
                        <i class="bi bi-check2-circle"></i>
                    @else
                        <span class="step-number">3</span>
                    @endif
                </div>
                <div class="step-label">Payment</div>
            </div>
        </div>
        <div class="col-3">
            <div class="step {{ $currentStep >= 4 ? 'active' : '' }}">
                <div class="step-circle">
                    <span class="step-number">4</span>
                </div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-steps {
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.checkout-steps .step {
    text-align: center;
    position: relative;
}

.checkout-steps .step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: bold;
    font-size: 1.25rem;
    transition: all 0.3s ease;
}

.checkout-steps .step.active .step-circle {
    background: #007bff;
    color: #fff;
}

.checkout-steps .step.completed .step-circle {
    background: #28a745;
    color: #fff;
}

.checkout-steps .step-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.checkout-steps .step.active .step-label {
    color: #007bff;
    font-weight: 600;
}

.checkout-steps .step.completed .step-label {
    color: #28a745;
}

@media (max-width: 767px) {
    .checkout-steps .step-label {
        font-size: 0.75rem;
    }
    
    .checkout-steps .step-circle {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>
