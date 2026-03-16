<x-admin-layout>
    @section('page-title')
        Edit Domain Registration
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Domain Registration</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.info', $domain) }}">{{ $domain->name }}</a></li>
                        <li class="breadcrumb-item active">Edit Registration</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form action="{{ route('admin.domains.update-registration', $domain) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Domain Registration Info</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Domain Name</label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ $domain->name }}"
                                           disabled>
                                    <small class="form-text text-muted">Domain name cannot be changed</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="owner_id" class="required">Owner</label>
                                    <select class="form-control select2bs4 @error('owner_id') is-invalid @enderror"
                                            id="owner_id"
                                            name="owner_id"
                                            required>
                                        <option value="">Select a user...</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('owner_id', $domain->owner_id) == $user->id)>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('owner_id')
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
                                           value="{{ old('years', $domain->years) }}"
                                           min="1"
                                           max="10"
                                           required>
                                    @error('years')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status" class="required">Status</label>
                                    <select class="form-control select2bs4 @error('status') is-invalid @enderror"
                                            id="status"
                                            name="status"
                                            required>
                                        @foreach($domainStatuses as $status)
                                            <option value="{{ $status->value }}"
                                                    @selected(old('status', $domain->status?->value) === $status->value)>
                                                {{ $status->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="auto_renew"
                                               name="auto_renew"
                                               value="1"
                                               @checked(old('auto_renew', $domain->auto_renew))>
                                        <label class="form-check-label" for="auto_renew">
                                            Enable automatic renewal
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registered_at" class="required">Registered At</label>
                                    <input type="date"
                                           class="form-control @error('registered_at') is-invalid @enderror"
                                           id="registered_at"
                                           name="registered_at"
                                           value="{{ old('registered_at', $domain->registered_at?->format('Y-m-d')) }}"
                                           required>
                                    @error('registered_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expires_at" class="required">Expires At</label>
                                    <input type="date"
                                           class="form-control @error('expires_at') is-invalid @enderror"
                                           id="expires_at"
                                           name="expires_at"
                                           value="{{ old('expires_at', $domain->expires_at?->format('Y-m-d')) }}"
                                           required>
                                    @error('expires_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Custom Pricing (Optional)</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Set a custom price for this domain. Leave empty to use standard TLD pricing.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="custom_price">Custom Price</label>
                                    <input type="number"
                                           class="form-control @error('custom_price') is-invalid @enderror"
                                           id="custom_price"
                                           name="custom_price"
                                           value="{{ old('custom_price', $domain->custom_price) }}"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                    @error('custom_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                                    @selected(old('custom_price_currency', $domain->custom_price_currency) === $currency->code)>
                                                {{ $currency->code }} - {{ $currency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('custom_price_currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="custom_price_notes">Custom Price Notes</label>
                                    <textarea class="form-control @error('custom_price_notes') is-invalid @enderror"
                                              id="custom_price_notes"
                                              name="custom_price_notes"
                                              rows="2"
                                              maxlength="1000"
                                              placeholder="Optional notes about the custom pricing...">{{ old('custom_price_notes', $domain->custom_price_notes) }}</textarea>
                                    @error('custom_price_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Hosting Subscription</h3>
                    </div>
                    <div class="card-body">
                        @if($domain->subscription)
                            <div class="alert alert-info">
                                <strong>Current Subscription:</strong>
                                #{{ $domain->subscription->id }}
                                — {{ $domain->subscription->plan?->name ?? 'N/A' }}
                                <span class="badge badge-{{ $domain->subscription->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($domain->subscription->status) }}
                                </span>
                                <br>
                                <small>
                                    Billing: {{ ucfirst($domain->subscription->billing_cycle) }}
                                    | Starts: {{ $domain->subscription->starts_at?->format('Y-m-d') }}
                                    | Expires: {{ $domain->subscription->expires_at?->format('Y-m-d') }}
                                </small>
                            </div>
                        @else
                            <div class="alert alert-secondary">
                                No subscription is currently linked to this domain.
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="required">Subscription Option</label>
                            @if($domain->subscription)
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="subscription_option" id="subscription_keep" value="keep_current" @checked(old('subscription_option', 'keep_current') === 'keep_current')>
                                    <label class="form-check-label" for="subscription_keep">
                                        Keep current subscription
                                    </label>
                                </div>
                            @endif
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="subscription_option" id="subscription_none" value="none" @checked(old('subscription_option', $domain->subscription ? 'keep_current' : 'none') === 'none')>
                                <label class="form-check-label" for="subscription_none">
                                    {{ $domain->subscription ? 'Remove/unlink subscription' : 'No hosting subscription' }}
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

                        <div id="create_subscription_section" style="display: none;">
                            <hr>
                            <h5>New Hosting Subscription</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="hosting_plan_id">Hosting Plan</label>
                                        <select class="form-control select2bs4 @error('hosting_plan_id') is-invalid @enderror"
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
                                        <select class="form-control select2bs4 @error('billing_cycle') is-invalid @enderror"
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
                                        <select class="form-control select2bs4 @error('hosting_custom_price_currency') is-invalid @enderror"
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

                        <div id="link_subscription_section" style="display: none;">
                            <hr>
                            <h5>Link to Existing Subscription</h5>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="existing_subscription_id">Select Subscription</label>
                                        <select class="form-control select2bs4 @error('existing_subscription_id') is-invalid @enderror"
                                                id="existing_subscription_id"
                                                name="existing_subscription_id">
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

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.domains.info', $domain) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Registration</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.select2bs4').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });

                // Subscription option toggle
                const subscriptionKeep = document.getElementById('subscription_keep');
                const subscriptionNone = document.getElementById('subscription_none');
                const subscriptionCreate = document.getElementById('subscription_create');
                const subscriptionLink = document.getElementById('subscription_link');
                const createSection = document.getElementById('create_subscription_section');
                const linkSection = document.getElementById('link_subscription_section');

                function updateSubscriptionSections() {
                    if (!createSection || !linkSection) return;

                    if (subscriptionCreate && subscriptionCreate.checked) {
                        createSection.style.display = 'block';
                        linkSection.style.display = 'none';
                        setTimeout(function() {
                            $('#hosting_plan_id, #billing_cycle').each(function() {
                                if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
                            });
                            $('#hosting_plan_id, #billing_cycle').select2({ theme: 'bootstrap4', width: '100%' });
                        }, 100);
                    } else if (subscriptionLink && subscriptionLink.checked) {
                        createSection.style.display = 'none';
                        linkSection.style.display = 'block';
                        setTimeout(function() {
                            if ($('#existing_subscription_id').hasClass('select2-hidden-accessible')) {
                                $('#existing_subscription_id').select2('destroy');
                            }
                            $('#existing_subscription_id').select2({ theme: 'bootstrap4', width: '100%' });
                        }, 100);
                    } else {
                        createSection.style.display = 'none';
                        linkSection.style.display = 'none';
                    }
                }

                if (subscriptionKeep) subscriptionKeep.addEventListener('change', updateSubscriptionSections);
                if (subscriptionNone) subscriptionNone.addEventListener('change', updateSubscriptionSections);
                if (subscriptionCreate) subscriptionCreate.addEventListener('change', updateSubscriptionSections);
                if (subscriptionLink) subscriptionLink.addEventListener('change', updateSubscriptionSections);

                updateSubscriptionSections();

                // Billing cycle date calculation
                const billingCycleSelect = document.getElementById('billing_cycle');
                const startsAtInput = document.getElementById('hosting_starts_at');
                const expiresAtInput = document.getElementById('hosting_expires_at');

                function updateExpiryDate() {
                    if (startsAtInput && expiresAtInput && billingCycleSelect && startsAtInput.value && billingCycleSelect.value) {
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

                if (billingCycleSelect) billingCycleSelect.addEventListener('change', updateExpiryDate);
                if (startsAtInput) startsAtInput.addEventListener('change', updateExpiryDate);
            });
        </script>
    @endpush
</x-admin-layout>
