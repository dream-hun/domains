<x-admin-layout>
    @section('page-title')
        Renew Domain
    @endsection

    <div class="container-fluid">
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Renew Domain</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h4>{{ $domain->name }}</h4>
                            <p class="text-muted">
                                <strong>Current Expiry Date:</strong> {{ $domain->expires_at->format('F d, Y') }}
                                @if($domain->expires_at->isPast())
                                    <span class="badge bg-danger">Expired</span>
                                @elseif($domain->expires_at->diffInDays() <= 30)
                                    <span class="badge bg-warning">Expiring Soon</span>
                                @endif
                            </p>
                            @if($domain->expires_at->diffInDays() > 0)
                                <p class="text-muted">
                                    <small>{{ $domain->expires_at->diffForHumans() }}</small>
                                </p>
                            @endif
                        </div>

                        <form action="{{ route('domains.renew.add-to-cart', $domain) }}" method="POST" x-data="{ years: 1, pricePerYear: @js($renewalPrice), currency: @js($currency) }">
                            @csrf

                            <div class="mb-3">
                                <label for="years" class="form-label">Renewal Period</label>
                                <select class="form-select" id="years" name="years" x-model="years" required>
                                    <option value="1">1 Year</option>
                                    <option value="2">2 Years</option>
                                    <option value="3">3 Years</option>
                                    <option value="4">4 Years</option>
                                    <option value="5">5 Years</option>
                                    <option value="6">6 Years</option>
                                    <option value="7">7 Years</option>
                                    <option value="8">8 Years</option>
                                    <option value="9">9 Years</option>
                                    <option value="10">10 Years</option>
                                </select>
                                @error('years')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Renewal Summary</h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Price per year:</span>
                                            <span><strong x-text="currency + ' ' + pricePerYear.toLocaleString()"></strong></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Number of years:</span>
                                            <span><strong x-text="years"></strong></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span class="h5 mb-0">Total:</span>
                                            <span class="h5 mb-0">
                                                <strong x-text="currency + ' ' + (pricePerYear * years).toLocaleString()"></strong>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span class="text-muted">New expiry date:</span>
                                            <span class="text-muted">
                                                {{ $domain->expires_at->copy()->addYears(1)->format('F d, Y') }}
                                                <span x-show="years > 1" x-text="'(+' + (years - 1) + ' year' + (years > 2 ? 's' : '') + ')'"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    Add to Cart & Proceed to Checkout
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading">Important Information:</h6>
                    <ul class="mb-0">
                        <li>Your domain will be renewed immediately upon successful payment.</li>
                        <li>The renewal period will be added to your current expiry date.</li>
                        <li>Renewal fees are non-refundable once the domain has been renewed.</li>
                        <li>You can renew your domain for up to 10 years at a time.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

