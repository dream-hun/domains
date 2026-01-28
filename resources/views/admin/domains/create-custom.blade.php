<x-admin-layout>
    @section('page-title')
        Custom Domain Registration
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Custom Domain Registration</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">Custom Register</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form action="{{ route('admin.domains.custom-register.store') }}" method="POST">
                @csrf

                {{-- Domain Registration Card --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Domain Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="domain_name" class="required">Domain Name</label>
                                    <input type="text"
                                           class="form-control @error('domain_name') is-invalid @enderror"
                                           id="domain_name"
                                           name="domain_name"
                                           value="{{ old('domain_name') }}"
                                           placeholder="example.com"
                                           required>
                                    @error('domain_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_id" class="required">Domain Owner</label>
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
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="years" class="required">Registration Period (Years)</label>
                                    <input type="number"
                                           class="form-control @error('years') is-invalid @enderror"
                                           id="years"
                                           name="years"
                                           value="{{ old('years', 1) }}"
                                           min="1"
                                           max="10"
                                           required>
                                    @error('years')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Contact Information</h5>
                        <p class="text-muted">Select contacts for the domain registration.</p>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registrant_contact_id" class="required">Registrant Contact</label>
                                    <select class="form-control select2 @error('registrant_contact_id') is-invalid @enderror"
                                            id="registrant_contact_id"
                                            name="registrant_contact_id"
                                            required
                                            style="width: 100%;">
                                        <option value="">Select Contact</option>
                                        @foreach ($contacts as $contact)
                                            <option value="{{ $contact->id }}" @selected(old('registrant_contact_id') == $contact->id)>
                                                {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('registrant_contact_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_contact_id" class="required">Admin Contact</label>
                                    <select class="form-control select2 @error('admin_contact_id') is-invalid @enderror"
                                            id="admin_contact_id"
                                            name="admin_contact_id"
                                            required
                                            style="width: 100%;">
                                        <option value="">Select Contact</option>
                                        @foreach ($contacts as $contact)
                                            <option value="{{ $contact->id }}" @selected(old('admin_contact_id') == $contact->id)>
                                                {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('admin_contact_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="technical_contact_id" class="required">Technical Contact</label>
                                    <select class="form-control select2 @error('technical_contact_id') is-invalid @enderror"
                                            id="technical_contact_id"
                                            name="technical_contact_id"
                                            required
                                            style="width: 100%;">
                                        <option value="">Select Contact</option>
                                        @foreach ($contacts as $contact)
                                            <option value="{{ $contact->id }}" @selected(old('technical_contact_id') == $contact->id)>
                                                {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('technical_contact_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_contact_id" class="required">Billing Contact</label>
                                    <select class="form-control select2 @error('billing_contact_id') is-invalid @enderror"
                                            id="billing_contact_id"
                                            name="billing_contact_id"
                                            required
                                            style="width: 100%;">
                                        <option value="">Select Contact</option>
                                        @foreach ($contacts as $contact)
                                            <option value="{{ $contact->id }}" @selected(old('billing_contact_id') == $contact->id)>
                                                {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('billing_contact_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Nameservers (Optional)</h5>
                        <p class="text-muted">If not specified, default nameservers will be used.</p>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nameserver_1">Nameserver 1</label>
                                    <input type="text"
                                           class="form-control @error('nameserver_1') is-invalid @enderror"
                                           id="nameserver_1"
                                           name="nameserver_1"
                                           value="{{ old('nameserver_1') }}"
                                           placeholder="ns1.example.com">
                                    @error('nameserver_1')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nameserver_2">Nameserver 2</label>
                                    <input type="text"
                                           class="form-control @error('nameserver_2') is-invalid @enderror"
                                           id="nameserver_2"
                                           name="nameserver_2"
                                           value="{{ old('nameserver_2') }}"
                                           placeholder="ns2.example.com">
                                    @error('nameserver_2')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nameserver_3">Nameserver 3</label>
                                    <input type="text"
                                           class="form-control @error('nameserver_3') is-invalid @enderror"
                                           id="nameserver_3"
                                           name="nameserver_3"
                                           value="{{ old('nameserver_3') }}"
                                           placeholder="ns3.example.com">
                                    @error('nameserver_3')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nameserver_4">Nameserver 4</label>
                                    <input type="text"
                                           class="form-control @error('nameserver_4') is-invalid @enderror"
                                           id="nameserver_4"
                                           name="nameserver_4"
                                           value="{{ old('nameserver_4') }}"
                                           placeholder="ns4.example.com">
                                    @error('nameserver_4')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Custom Domain Pricing Card --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Custom Domain Pricing (Optional)</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Set a custom price for this domain. Leave empty to use standard TLD pricing.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="domain_custom_price">Custom Price</label>
                                    <input type="number"
                                           class="form-control @error('domain_custom_price') is-invalid @enderror"
                                           id="domain_custom_price"
                                           name="domain_custom_price"
                                           value="{{ old('domain_custom_price') }}"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                    @error('domain_custom_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="domain_custom_price_currency">Currency</label>
                                    <select class="form-control @error('domain_custom_price_currency') is-invalid @enderror"
                                            id="domain_custom_price_currency"
                                            name="domain_custom_price_currency">
                                        <option value="">Select currency...</option>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->code }}"
                                                    @selected(old('domain_custom_price_currency', 'USD') === $currency->code)>
                                                {{ $currency->code }} - {{ $currency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('domain_custom_price_currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="domain_custom_price_notes">Custom Price Notes</label>
                                    <textarea class="form-control @error('domain_custom_price_notes') is-invalid @enderror"
                                              id="domain_custom_price_notes"
                                              name="domain_custom_price_notes"
                                              rows="2"
                                              maxlength="1000"
                                              placeholder="Optional notes about the custom pricing...">{{ old('domain_custom_price_notes') }}</textarea>
                                    @error('domain_custom_price_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hosting Subscription Card --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Hosting Subscription</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="required">Subscription Option</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="subscription_option" id="subscription_none" value="none" @checked(old('subscription_option', 'none') === 'none')>
                                <label class="form-check-label" for="subscription_none">
                                    No hosting subscription
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="subscription_option" id="subscription_create" value="create_new" @checked(old('subscription_option') === 'create_new')>
                                <label class="form-check-label" for="subscription_create">
                                    Create new hosting subscription
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="subscription_option" id="subscription_link" value="link_existing" @checked(old('subscription_option') === 'link_existing')>
                                <label class="form-check-label" for="subscription_link">
                                    Link to existing subscription
                                </label>
                            </div>
                            @error('subscription_option')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Create New Subscription Section --}}
                        <div id="create_subscription_section" style="display: none;">
                            <hr>
                            <h5>New Hosting Subscription</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hosting_plan_id">Hosting Plan</label>
                                        <select class="form-control @error('hosting_plan_id') is-invalid @enderror"
                                                id="hosting_plan_id"
                                                name="hosting_plan_id">
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

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="billing_cycle">Billing Cycle</label>
                                        <select class="form-control @error('billing_cycle') is-invalid @enderror"
                                                id="billing_cycle"
                                                name="billing_cycle">
                                            <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Monthly</option>
                                            <option value="annually" @selected(old('billing_cycle') === 'annually')>Annually</option>
                                        </select>
                                        @error('billing_cycle')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hosting_starts_at">Start Date</label>
                                        <input type="date"
                                               class="form-control @error('hosting_starts_at') is-invalid @enderror"
                                               id="hosting_starts_at"
                                               name="hosting_starts_at"
                                               value="{{ old('hosting_starts_at', now()->format('Y-m-d')) }}">
                                        @error('hosting_starts_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hosting_expires_at">Expiry Date</label>
                                        <input type="date"
                                               class="form-control @error('hosting_expires_at') is-invalid @enderror"
                                               id="hosting_expires_at"
                                               name="hosting_expires_at"
                                               value="{{ old('hosting_expires_at') }}">
                                        @error('hosting_expires_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="hosting_auto_renew"
                                                   name="hosting_auto_renew"
                                                   value="1"
                                                   @checked(old('hosting_auto_renew', false))>
                                            <label class="form-check-label" for="hosting_auto_renew">
                                                Enable automatic renewal
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6>Custom Hosting Price (Optional)</h6>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="hosting_custom_price">Custom Price</label>
                                        <input type="number"
                                               class="form-control @error('hosting_custom_price') is-invalid @enderror"
                                               id="hosting_custom_price"
                                               name="hosting_custom_price"
                                               value="{{ old('hosting_custom_price') }}"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00">
                                        @error('hosting_custom_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="hosting_custom_price_currency">Currency</label>
                                        <select class="form-control @error('hosting_custom_price_currency') is-invalid @enderror"
                                                id="hosting_custom_price_currency"
                                                name="hosting_custom_price_currency">
                                            <option value="">Select currency...</option>
                                            @foreach($currencies as $currency)
                                                <option value="{{ $currency->code }}"
                                                        @selected(old('hosting_custom_price_currency', 'USD') === $currency->code)>
                                                    {{ $currency->code }} - {{ $currency->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('hosting_custom_price_currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="hosting_custom_price_notes">Custom Price Notes</label>
                                        <textarea class="form-control @error('hosting_custom_price_notes') is-invalid @enderror"
                                                  id="hosting_custom_price_notes"
                                                  name="hosting_custom_price_notes"
                                                  rows="2"
                                                  maxlength="1000"
                                                  placeholder="Optional notes about the custom pricing...">{{ old('hosting_custom_price_notes') }}</textarea>
                                        @error('hosting_custom_price_notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Link Existing Subscription Section --}}
                        <div id="link_subscription_section" style="display: none;">
                            <hr>
                            <h5>Link to Existing Subscription</h5>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="existing_subscription_id">Select Subscription</label>
                                        <select class="form-control select2 @error('existing_subscription_id') is-invalid @enderror"
                                                id="existing_subscription_id"
                                                name="existing_subscription_id"
                                                style="width: 100%;">
                                            <option value="">Select a subscription...</option>
                                            @foreach($subscriptions as $subscription)
                                                <option value="{{ $subscription->id }}" @selected(old('existing_subscription_id') == $subscription->id)>
                                                    #{{ $subscription->id }} - {{ $subscription->plan?->name ?? 'N/A' }}
                                                    ({{ $subscription->user?->name ?? 'Unknown' }})
                                                    @if($subscription->domain)
                                                        - {{ $subscription->domain }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('existing_subscription_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            Register Domain
                        </button>
                        <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Select2
                $('.select2').select2({
                    placeholder: 'Select an option...',
                    allowClear: true
                });

                // Subscription option toggle
                const subscriptionNone = document.getElementById('subscription_none');
                const subscriptionCreate = document.getElementById('subscription_create');
                const subscriptionLink = document.getElementById('subscription_link');
                const createSection = document.getElementById('create_subscription_section');
                const linkSection = document.getElementById('link_subscription_section');

                function updateSubscriptionSections() {
                    if (subscriptionCreate.checked) {
                        createSection.style.display = 'block';
                        linkSection.style.display = 'none';
                    } else if (subscriptionLink.checked) {
                        createSection.style.display = 'none';
                        linkSection.style.display = 'block';
                    } else {
                        createSection.style.display = 'none';
                        linkSection.style.display = 'none';
                    }
                }

                subscriptionNone.addEventListener('change', updateSubscriptionSections);
                subscriptionCreate.addEventListener('change', updateSubscriptionSections);
                subscriptionLink.addEventListener('change', updateSubscriptionSections);

                // Initialize on page load
                updateSubscriptionSections();

                // Billing cycle date calculation
                const billingCycleSelect = document.getElementById('billing_cycle');
                const startsAtInput = document.getElementById('hosting_starts_at');
                const expiresAtInput = document.getElementById('hosting_expires_at');

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
