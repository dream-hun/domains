<x-admin-layout>
    @section('page-title')
        Create Custom Subscription
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create Custom Subscription</h1>
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
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i> Create Custom Subscription
                            </h3>
                        </div>
                        <form action="{{ route('admin.subscriptions.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="user_id">
                                                <i class="fas fa-user"></i> User <span class="text-danger">*</span>
                                            </label>
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
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hosting_plan_id">
                                                <i class="fas fa-server"></i> Hosting Plan <span class="text-danger">*</span>
                                            </label>
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
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="billing_cycle">
                                                <i class="fas fa-sync-alt"></i> Billing Cycle <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control @error('billing_cycle') is-invalid @enderror"
                                                    id="billing_cycle"
                                                    name="billing_cycle"
                                                    required>
                                                <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Monthly</option>
                                                <option value="annually" @selected(old('billing_cycle') === 'annually')>Annually</option>
                                            </select>
                                            @error('billing_cycle')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="custom_price">
                                                <i class="fas fa-dollar-sign"></i> Custom Price <small class="text-muted">(Optional)</small>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                                </div>
                                                <input type="number"
                                                       class="form-control @error('custom_price') is-invalid @enderror"
                                                       id="custom_price"
                                                       name="custom_price"
                                                       value="{{ old('custom_price') }}"
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="Leave empty to use plan price">
                                                @error('custom_price')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle"></i> If not provided, the plan's renewal price will be used
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="custom_price_currency">
                                                <i class="fas fa-coins"></i> Currency
                                            </label>
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
                                                <span class="error invalid-feedback">{{ $message }}</span>
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
                                            <label for="domain">
                                                <i class="fas fa-globe"></i> Domain <small class="text-muted">(Optional)</small>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                                </div>
                                                <input type="text"
                                                       class="form-control @error('domain') is-invalid @enderror"
                                                       id="domain"
                                                       name="domain"
                                                       value="{{ old('domain') }}"
                                                       placeholder="example.com">
                                                @error('domain')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <small class="form-text text-muted">
                                                Leave empty for VPS or standalone hosting
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-sync"></i> Auto Renewal Settings
                                            </label>
                                            <div class="custom-control custom-switch custom-switch-lg">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="auto_renew"
                                                       name="auto_renew"
                                                       value="1"
                                                       @checked(old('auto_renew', false))>
                                                <label class="custom-control-label" for="auto_renew">
                                                    Enable automatic renewal
                                                </label>
                                            </div>
                                            @error('auto_renew')
                                                <span class="error invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="starts_at">
                                                <i class="fas fa-calendar-check"></i> Start Date <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group date" id="starts_at_wrapper" data-target-input="nearest">
                                                <input type="date"
                                                       class="form-control @error('starts_at') is-invalid @enderror"
                                                       id="starts_at"
                                                       name="starts_at"
                                                       value="{{ old('starts_at', now()->format('Y-m-d')) }}"
                                                       required>
                                                <div class="input-group-append" data-target="#starts_at_wrapper" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                                @error('starts_at')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="expires_at">
                                                <i class="fas fa-calendar-times"></i> Expiry Date <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group date" id="expires_at_wrapper" data-target-input="nearest">
                                                <input type="date"
                                                       class="form-control @error('expires_at') is-invalid @enderror"
                                                       id="expires_at"
                                                       name="expires_at"
                                                       value="{{ old('expires_at') }}"
                                                       required>
                                                <div class="input-group-append" data-target="#expires_at_wrapper" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                                @error('expires_at')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="custom_price_notes">
                                                <i class="fas fa-sticky-note"></i> Custom Price Notes <small class="text-muted">(Optional)</small>
                                            </label>
                                            <textarea class="form-control @error('custom_price_notes') is-invalid @enderror"
                                                      id="custom_price_notes"
                                                      name="custom_price_notes"
                                                      rows="3"
                                                      maxlength="1000"
                                                      placeholder="Optional notes about the custom pricing...">{{ old('custom_price_notes') }}</textarea>
                                            @error('custom_price_notes')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Subscription
                                </button>
                                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
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
                    allowClear: true,
                    theme: 'bootstrap4'
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
