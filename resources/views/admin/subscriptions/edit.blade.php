<x-admin-layout>
    @section('page-title')
        Edit Subscription
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Subscription</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.show', $subscription) }}">Details</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Edit Subscription #{{ $subscription->plan?->name }}</h3>
                        </div>
                        <form action="{{ route('admin.subscriptions.update', $subscription) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status" class="required">Status</label>
                                            <select class="form-control select2bs4 @error('status') is-invalid @enderror"
                                                    id="status"
                                                    name="status"
                                                    required>
                                                @foreach($statusOptions as $status)
                                                    <option value="{{ $status }}"
                                                            @selected(old('status', $subscription->status) === $status)>
                                                        {{ ucfirst($status) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Current status: <strong>{{ ucfirst($subscription->status) }}</strong>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="domain">Linked Domain</label>
                                            <input type="text"
                                                   class="form-control @error('domain') is-invalid @enderror"
                                                   id="domain"
                                                   name="domain"
                                                   value="{{ old('domain', $subscription->domain) }}"
                                                   placeholder="example.com">
                                            @error('domain')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Leave empty for VPS or standalone hosting
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="starts_at" class="required">Start Date</label>
                                            <input type="datetime-local"
                                                   class="form-control @error('starts_at') is-invalid @enderror"
                                                   id="starts_at"
                                                   name="starts_at"
                                                   value="{{ old('starts_at', $subscription->starts_at?->format('Y-m-d\TH:i')) }}"
                                                   required>
                                            @error('starts_at')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="expires_at" class="required">Expiry Date</label>
                                            <input type="datetime-local"
                                                   class="form-control @error('expires_at') is-invalid @enderror"
                                                   id="expires_at"
                                                   name="expires_at"
                                                   value="{{ old('expires_at', $subscription->expires_at?->format('Y-m-d\TH:i')) }}"
                                                   required>
                                            @error('expires_at')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="next_renewal_at">Next Renewal Date</label>
                                            <input type="datetime-local"
                                                   class="form-control @error('next_renewal_at') is-invalid @enderror"
                                                   id="next_renewal_at"
                                                   name="next_renewal_at"
                                                   value="{{ old('next_renewal_at', $subscription->next_renewal_at?->format('Y-m-d\TH:i')) }}">
                                            @error('next_renewal_at')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Usually same as expiry date
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="billing_cycle" class="required">Billing Cycle</label>
                                            <select class="form-control select2bs4 @error('billing_cycle') is-invalid @enderror"
                                                    id="billing_cycle"
                                                    name="billing_cycle"
                                                    required>
                                                @foreach($billingCycleOptions as $cycle)
                                                    <option value="{{ $cycle->value }}"
                                                            @selected(old('billing_cycle', $subscription->billing_cycle) === $cycle->value)>
                                                        {{ $cycle->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('billing_cycle')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="form-check mt-4">
                                                <input type="checkbox"
                                                       class="form-check-input"
                                                       id="auto_renew"
                                                       name="auto_renew"
                                                       value="1"
                                                       @checked(old('auto_renew', $subscription->auto_renew))>
                                                <label class="form-check-label" for="auto_renew">
                                                    Enable Auto-Renewal
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                If enabled, subscription will automatically renew before expiration
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <h5 class="mb-3">Custom Pricing <small class="text-muted">(Optional)</small></h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="custom_price">Custom Price</label>
                                            <input type="number"
                                                   class="form-control @error('custom_price') is-invalid @enderror"
                                                   id="custom_price"
                                                   name="custom_price"
                                                   value="{{ old('custom_price', $subscription->custom_price) }}"
                                                   step="0.01"
                                                   min="0"
                                                   placeholder="Leave empty to use plan price">
                                            @error('custom_price')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                If not provided, the plan's renewal price will be used
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="custom_price_currency">Currency</label>
                                            <select class="form-control select2bs4 @error('custom_price_currency') is-invalid @enderror"
                                                    id="custom_price_currency"
                                                    name="custom_price_currency">
                                                <option value="">Select currency...</option>
                                                @foreach($currencies as $currency)
                                                    <option value="{{ $currency->code }}"
                                                            @selected(old('custom_price_currency', $subscription->custom_price_currency) === $currency->code)>
                                                        {{ $currency->code }} - {{ $currency->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('custom_price_currency')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Required if custom price is provided
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="custom_price_notes">Custom Price Notes</label>
                                            <textarea class="form-control @error('custom_price_notes') is-invalid @enderror"
                                                      id="custom_price_notes"
                                                      name="custom_price_notes"
                                                      rows="3"
                                                      maxlength="1000"
                                                      placeholder="Optional notes about the custom pricing...">{{ old('custom_price_notes', $subscription->custom_price_notes) }}</textarea>
                                            @error('custom_price_notes')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <h5 class="mb-3">Read-Only Information</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Customer:</strong> {{ $subscription->user?->name ?? 'N/A' }}</p>
                                        <p><strong>Plan:</strong> {{ $subscription->plan?->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        @if($subscription->planPrice)
                                            <p><strong>Regular Price:</strong> {{ $subscription->planPrice->getFormattedPrice('regular_price') }}</p>
                                            <p><strong>Renewal Price:</strong> {{ $subscription->planPrice->getFormattedPrice('renewal_price') }}</p>
                                        @endif
                                        @if($subscription->provider_resource_id)
                                            <p><strong>Provider Reference:</strong> <code>{{ $subscription->provider_resource_id }}</code></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Update Subscription</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        @parent
        <script>
            $(function () {
                // Initialize select2 for form dropdowns
                $('.select2bs4').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });
            });
        </script>
    @endsection
</x-admin-layout>
