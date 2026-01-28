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
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Create Custom Subscription</h3>
                        </div>
                        <form action="{{ route('admin.subscriptions.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="user_id" class="required">User</label>
                                            <select class="form-control select2 @error('user_id') is-invalid @enderror"
                                                    id="user_id"
                                                    name="user_id"
                                                    required>
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
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
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
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="domain">Domain <small class="text-muted">(Optional)</small></label>
                                            <input type="text"
                                                   class="form-control @error('domain') is-invalid @enderror"
                                                   id="domain"
                                                   name="domain"
                                                   value="{{ old('domain') }}"
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
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="custom_price">Custom Price <small class="text-muted">(Optional)</small></label>
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
                                            <small class="form-text text-muted">
                                                If not provided, the plan's renewal price will be used
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
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
                                            <label for="starts_at" class="required">Start Date</label>
                                            <input type="date"
                                                   class="form-control @error('starts_at') is-invalid @enderror"
                                                   id="starts_at"
                                                   name="starts_at"
                                                   value="{{ old('starts_at', now()->format('Y-m-d')) }}"
                                                   required>
                                            @error('starts_at')
                                                <span class="error invalid-feedback">{{ $message }}</span>
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
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Auto Renew</label>
                                            <div class="form-check mt-2">
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
                                                <span class="error invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="custom_price_notes">Custom Price Notes <small class="text-muted">(Optional)</small></label>
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
                                <button type="submit" class="btn btn-primary">Create Subscription</button>
                                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <style>
            /* Select2 Custom Styles */
            .select2-container--default .select2-selection--single {
                height: 38px;
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 38px;
                padding-left: 12px;
                color: #495057;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 36px;
                right: 10px;
            }

            .select2-container--default .select2-results__option--highlighted[aria-selected] {
                background-color: #007bff;
            }

            .select2-container--default .select2-results__option[aria-selected=true] {
                background-color: #e9ecef;
            }

            .select2-dropdown {
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
            }

            .select2-container--default .select2-search--dropdown .select2-search__field {
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
            }

            .select2-container--default .select2-search--dropdown .select2-search__field:focus {
                border-color: #80bdff;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            /* Form Alignment */
            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-group label {
                font-weight: 500;
                margin-bottom: 0.5rem;
                display: block;
            }

            .form-group label.required::after {
                content: " *";
                color: #dc3545;
            }

            .form-group small.text-muted {
                display: block;
                margin-top: 0.25rem;
                font-size: 0.875rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                $('#user_id').select2({
                    placeholder: 'Select a user...',
                    allowClear: true,
                    width: '100%'
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
