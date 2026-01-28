<x-admin-layout>
    @section('page-title')
        Create Custom Subscription
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create Custom Subscription</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Create Custom Subscription</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.subscriptions.store') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="user_id" class="required">User</label>
                                            <select class="form-control select2 @error('user_id') is-invalid @enderror"
                                                    id="user_id"
                                                    name="user_id"
                                                    required
                                                    style="width: 100%;">
                                                <option value="">Select a user...</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                                        {{ $user->name }} ({{ $user->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('user_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hosting_plan_id" class="required">Hosting Plan</label>
                                            <select class="form-control @error('hosting_plan_id') is-invalid @enderror"
                                                    id="hosting_plan_id"
                                                    name="hosting_plan_id"
                                                    required>
                                                <option value="">Select a hosting plan...</option>
                                                @foreach($hostingPlans as $plan)
                                                    <option value="{{ $plan->id }}" @selected(old('hosting_plan_id') == $plan->id)>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('hosting_plan_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="billing_cycle" class="required">Billing Cycle</label>
                                            <select class="form-control @error('billing_cycle') is-invalid @enderror"
                                                    id="billing_cycle"
                                                    name="billing_cycle"
                                                    required>
                                                <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Monthly</option>
                                                <option value="annually" @selected(old('billing_cycle') === 'annually')>Annually</option>
                                            </select>
                                            @error('billing_cycle')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="custom_price">Custom Price (Optional)</label>
                                            <input type="number"
                                                   class="form-control @error('custom_price') is-invalid @enderror"
                                                   id="custom_price"
                                                   name="custom_price"
                                                   value="{{ old('custom_price') }}"
                                                   step="0.01"
                                                   min="0"
                                                   placeholder="Leave empty to use plan price">
                                            @error('custom_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                If not provided, the plan's renewal price will be used
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="custom_price_currency">Currency</label>
                                            <select class="form-control @error('custom_price_currency') is-invalid @enderror"
                                                    id="custom_price_currency"
                                                    name="custom_price_currency">
                                                <option value="">Select currency...</option>
                                                @foreach($currencies as $currency)
                                                    <option value="{{ $currency->code }}" 
                                                            @selected(old('custom_price_currency', 'USD') === $currency->code)>
                                                        {{ $currency->code }} - {{ $currency->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('custom_price_currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Required if custom price is provided
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="domain">Domain (Optional)</label>
                                            <input type="text"
                                                   class="form-control @error('domain') is-invalid @enderror"
                                                   id="domain"
                                                   name="domain"
                                                   value="{{ old('domain') }}"
                                                   placeholder="example.com">
                                            @error('domain')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Leave empty for VPS or standalone hosting
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="auto_renew">Auto Renew</label>
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="auto_renew"
                                                       name="auto_renew"
                                                       value="1"
                                                       @checked(old('auto_renew', false))>
                                                <label class="form-check-label" for="auto_renew">
                                                    Enable automatic renewal
                                                </label>
                                            </div>
                                            @error('auto_renew')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="starts_at" class="required">Start Date</label>
                                            <input type="date"
                                                   class="form-control @error('starts_at') is-invalid @enderror"
                                                   id="starts_at"
                                                   name="starts_at"
                                                   value="{{ old('starts_at', now()->format('Y-m-d')) }}"
                                                   required>
                                            @error('starts_at')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expires_at" class="required">Expiry Date</label>
                                            <input type="date"
                                                   class="form-control @error('expires_at') is-invalid @enderror"
                                                   id="expires_at"
                                                   name="expires_at"
                                                   value="{{ old('expires_at') }}"
                                                   required>
                                            @error('expires_at')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="custom_price_notes">Custom Price Notes (Optional)</label>
                                            <textarea class="form-control @error('custom_price_notes') is-invalid @enderror"
                                                      id="custom_price_notes"
                                                      name="custom_price_notes"
                                                      rows="3"
                                                      maxlength="1000"
                                                      placeholder="Optional notes about the custom pricing...">{{ old('custom_price_notes') }}</textarea>
                                            @error('custom_price_notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        Create Subscription
                                    </button>
                                    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                $('#user_id').select2({
                    placeholder: 'Select a user...',
                    allowClear: true
                });

                const billingCycleSelect = document.getElementById('billing_cycle');
                const startsAtInput = document.getElementById('starts_at');
                const expiresAtInput = document.getElementById('expires_at');

                function updateExpiryDate() {
                    if (startsAtInput.value && billingCycleSelect.value) {
                        const startDate = new Date(startsAtInput.value);
                        const expiryDate = new Date(startDate);

                        if (billingCycleSelect.value === 'annually') {
                            expiryDate.setFullYear(expiryDate.getFullYear() + 1);
                        } else {
                            expiryDate.setMonth(expiryDate.getMonth() + 1);
                        }

                        expiresAtInput.value = expiryDate.toISOString().split('T')[0];
                    }
                }

                billingCycleSelect.addEventListener('change', updateExpiryDate);
                startsAtInput.addEventListener('change', updateExpiryDate);
            });
        </script>
    @endpush
</x-admin-layout>
