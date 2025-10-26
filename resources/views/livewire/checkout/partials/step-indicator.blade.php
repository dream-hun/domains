<nav aria-label="Checkout progress" class="checkout-steps mb-4 py-4">
    <ol class="row list-unstyled mb-0">
        <li class="col-3">
            <div class="step {{ $currentStep >= 1 ? 'active' : '' }} {{ $currentStep > 1 ? 'completed' : '' }}" 
                 aria-current="{{ $currentStep === 1 ? 'step' : 'false' }}">
                <div class="step-circle" aria-hidden="true">
                    @if($currentStep > 1)
                        <i class="bi bi-check2-circle"></i>
                    @else
                        <span class="step-number">1</span>
                    @endif
                </div>
                <div class="step-label">Review Order</div>
            </div>
        </li>
        <li class="col-3">
            <div class="step {{ $currentStep >= 2 ? 'active' : '' }} {{ $currentStep > 2 ? 'completed' : '' }}"
                 aria-current="{{ $currentStep === 2 ? 'step' : 'false' }}">
                <div class="step-circle" aria-hidden="true">
                    @if($currentStep > 2)
                        <i class="bi bi-check2-circle"></i>
                    @else
                        <span class="step-number">2</span>
                    @endif
                </div>
                <div class="step-label">Contact Information</div>
            </div>
        </li>
        <li class="col-3">
            <div class="step {{ $currentStep >= 3 ? 'active' : '' }} {{ $currentStep > 3 ? 'completed' : '' }}"
                 aria-current="{{ $currentStep === 3 ? 'step' : 'false' }}">
                <div class="step-circle" aria-hidden="true">
                    @if($currentStep > 3)
                        <i class="bi bi-check2-circle"></i>
                    @else
                        <span class="step-number">3</span>
                    @endif
                </div>
                <div class="step-label">Payment</div>
            </div>
        </li>
        <li class="col-3">
            <div class="step {{ $currentStep >= 4 ? 'active' : '' }}"
                 aria-current="{{ $currentStep === 4 ? 'step' : 'false' }}">
                <div class="step-circle" aria-hidden="true">
                    <span class="step-number">4</span>
                </div>
                <div class="step-label">Confirmation</div>
            </div>
        </li>
    </ol>
</nav>
