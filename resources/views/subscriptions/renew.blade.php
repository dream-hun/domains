<x-user-layout>
    @section('page-title')
        Renew Subscription
    @endsection

    <div class="container py-5">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Renew Subscription</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h4>{{ $subscription->plan->name }}</h4>
                            @if($subscription->domain)
                                <p class="text-muted mb-2">
                                    <strong>Domain:</strong> {{ $subscription->domain }}
                                </p>
                            @endif
                            <p class="text-muted mb-2">
                                <strong>Current Billing Cycle:</strong>
                                <span class="badge bg-info">{{ $currentBillingCycle->label() }}</span>
                            </p>
                            <p class="text-muted mb-2">
                                <strong>Current Expiry Date:</strong>
                                {{ $subscription->expires_at->format('F d, Y') }}
                                @if($subscription->expires_at->isPast())
                                    <span class="badge bg-danger ms-2">Expired</span>
                                @elseif($subscription->expires_at->diffInDays(now()) <= 30)
                                    <span class="badge bg-warning ms-2">Expiring Soon</span>
                                @endif
                            </p>
                            @if($subscription->expires_at->isFuture())
                                <p class="text-muted">
                                    <small>{{ $subscription->expires_at->diffForHumans() }}</small>
                                </p>
                            @endif
                        </div>

                        @php
                            $monthlyPrice = $availableBillingCycles['monthly']['renewal_price'] ?? 0;
                            $defaultBillingCycle = BillingCycle::tryFrom($subscription->billing_cycle) ?? BillingCycle::Monthly;
                            $defaultQuantity = $defaultBillingCycle->toMonths();
                        @endphp
                        <form action="{{ route('subscriptions.renew.add-to-cart', $subscription) }}" method="POST"
                              x-data="{
                                  billingCycle: '{{ $subscription->billing_cycle }}',
                                  quantity: {{ $defaultQuantity }},
                                  monthlyPrice: {{ $monthlyPrice }},
                                  availableCycles: @js($availableBillingCycles),
                                  cycleToMonths: {
                                      'monthly': 1,
                                      'annually': 12
                                  },
                                  originalQuantity: {{ $defaultQuantity }},
                                  updateQuantityFromBillingCycle() {
                                      const months = this.cycleToMonths[this.billingCycle] || 1;
                                      // Update quantity to match selected billing cycle
                                      this.quantity = months;
                                      this.originalQuantity = months;
                                  },
                                  get selectedCycleLabel() {
                                      const cycle = this.availableCycles[this.billingCycle]?.cycle;
                                      return cycle ? cycle.label : '';
                                  },
                                  get totalPrice() {
                                      return this.monthlyPrice * this.quantity;
                                  },
                                  get newExpiryDate() {
                                      const currentExpiry = new Date('{{ $subscription->expires_at->toIso8601String() }}');
                                      const newDate = new Date(currentExpiry);
                                      newDate.setMonth(newDate.getMonth() + this.quantity);
                                      return newDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                                  }
                              }">
                            @csrf

                            <div class="mb-4">
                                <label for="billing_cycle" class="form-label fw-bold">Billing Cycle Reference</label>
                                <select class="form-select form-select-lg" id="billing_cycle" name="billing_cycle"
                                        x-model="billingCycle"
                                        @change="updateQuantityFromBillingCycle()"
                                        required>
                                    @foreach($availableBillingCycles as $cycleValue => $cycleData)
                                        <option value="{{ $cycleValue }}" @selected($cycleValue === $subscription->billing_cycle)>
                                            {{ $cycleData['cycle']->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('billing_cycle')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Select a billing cycle to quickly set the renewal period. You can also manually enter any number of months below.
                                </small>
                            </div>

                            <div class="mb-4">
                                <label for="quantity" class="form-label fw-bold">Renewal Period (Months)</label>
                                <input type="number"
                                       class="form-control form-control-lg"
                                       id="quantity"
                                       name="quantity"
                                       x-model.number="quantity"
                                       min="1"
                                       max="120"
                                       required>
                                @error('quantity')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Enter the number of months to renew (1-120 months, up to 10 years).
                                </small>
                            </div>

                            <div class="mb-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title mb-3">Renewal Summary</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Monthly Renewal Price:</span>
                                            <span><strong>$<span x-text="monthlyPrice.toFixed(2)"></span></strong></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Renewal Period:</span>
                                            <span><strong x-text="quantity + (quantity === 1 ? ' month' : ' months')"></strong></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="h5 mb-0">Total:</span>
                                            <span class="h5 mb-0">
                                                <strong>$<span x-text="totalPrice.toFixed(2)"></span></strong>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3">
                                            <span class="text-muted">New expiry date:</span>
                                            <span class="text-muted fw-semibold" x-text="newExpiryDate"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-cart-plus me-1"></i> Add to Cart & Proceed to Checkout
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>
