<div>
    @if ($hasDomains && !empty($plans))
        <section class="mt-4" style="font-size: 18px; !important;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="text-muted mb-0 fw-normal">Add Hosting</h6>
                <select class="form-select form-select-sm border-0 bg-transparent text-muted" style="width: auto;"
                    wire:model.live="selectedDomain">
                    @foreach ($cartDomains as $domain)
                        <option value="{{ $domain }}">{{ $domain }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row g-3">
                @foreach ($plans as $plan)
                    @php
                        $price = $plan['monthly_price'];
                        $billingCycle = $price['billing_cycle'] ?? 'monthly';
                    @endphp
                    <div class="col">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <p class="text-muted small mb-1">{{ $plan['name'] }}</p>
                            <div class="d-flex align-items-baseline gap-1 mb-2">
                                <span class="fs-4 fw-semibold text-dark">{{ $price['formatted'] ?? '—' }}</span>
                                <span
                                    class="text-muted small">/{{ $billingCycle === 'monthly' ? 'Month' : $billingCycle }}</span>
                            </div>
                            @if ($this->isHostingInCart($plan['id']))
                                <button class="btn btn-md btn-danger w-50" style="font-size: 18px;"
                                    wire:click="removeHosting({{ $plan['id'] }})">
                                    <i class="bi bi-x-circle"></i>
                                    Remove
                                </button>
                            @else
                                <button class="btn btn-md btn-success w-50" style="font-size: 18px;"
                                    wire:click="addHosting({{ $plan['id'] }}, '{{ $billingCycle }}')">
                                    <i class="bi bi-cart4"></i>
                                    Add to Cart
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @elseif ($hasDomains)
        <div class="alert alert-info mt-4">
            Hosting plans are not available right now. Please try again later.
        </div>
    @endif
</div>
