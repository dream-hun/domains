<x-admin-layout>
    @section('page-title')
        Edit {{ ucfirst($contactType) }} Contact
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit {{ ucfirst($contactType) }} Contact</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('admin.domains.edit', $domain->uuid) }}">{{ $domain->name }}</a></li>
                        <li class="breadcrumb-item active">Edit {{ ucfirst($contactType) }} Contact</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-person-gear"></i> Edit {{ ucfirst($contactType) }} Contact
                            </h3>

                        </div>

                        <form action="{{ route('admin.domains.contacts.update', $domain->uuid) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Address Book Selection (Hidden by default) -->
                                <div id="addressBookSection" class="card bg-light mb-4" style="display: none;">
                                    <div class="card-body">
                                        <h6>Select from Address Book:</h6>
                                        <select id="contact_select" class="form-control mb-3">
                                            <option value="">Choose a contact...</option>
                                            @foreach ($availableContacts as $contact)
                                                <option value="{{ $contact->id }}"
                                                        data-first-name="{{ $contact->first_name }}"
                                                        data-last-name="{{ $contact->last_name }}"
                                                        data-organization="{{ $contact->organization }}"
                                                        data-address-one="{{ $contact->address_one }}"
                                                        data-address-two="{{ $contact->address_two }}"
                                                        data-city="{{ $contact->city }}"
                                                        data-state-province="{{ $contact->state_province }}"
                                                        data-postal-code="{{ $contact->postal_code }}"
                                                        data-country-code="{{ $contact->country_code }}"
                                                        data-phone="{{ $contact->phone }}"
                                                        data-email="{{ $contact->email }}">
                                                    {{ $contact->full_name }}
                                                    ({{ $contact->email }}){{ $contact->is_primary ? ' - PRIMARY' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-sm btn-secondary" id="hideAddressBookBtn">
                                            <i class="bi bi-x"></i> Cancel
                                        </button>
                                    </div>
                                </div>

                                <!-- Contact Form Fields -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text"
                                                   class="form-control @error('first_name') is-invalid @enderror"
                                                   id="first_name" name="first_name"
                                                   value="{{ old('first_name', $currentContact->first_name ?? '') }}"
                                                   required>
                                            @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text"
                                                   class="form-control @error('last_name') is-invalid @enderror"
                                                   id="last_name" name="last_name"
                                                   value="{{ old('last_name', $currentContact->last_name ?? '') }}"
                                                   required>
                                            @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_organization"
                                               name="is_organization"
                                            {{ old('is_organization', $currentContact->organization ? 'checked' : '') }}>
                                        <label class="form-check-label" for="is_organization">
                                            Domain is registered on behalf of a company
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group mb-3" id="organization_field"
                                     style="{{ old('is_organization', $currentContact->organization) ? '' : 'display: none;' }}">
                                    <label for="organization" class="form-label">Organization</label>
                                    <input type="text"
                                           class="form-control @error('organization') is-invalid @enderror"
                                           id="organization" name="organization"
                                           value="{{ old('organization', $currentContact->organization ?? '') }}">
                                    @error('organization')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="address_one" class="form-label">Address</label>
                                    <input type="text"
                                           class="form-control @error('address_one') is-invalid @enderror" id="address_one"
                                           name="address_one"
                                           value="{{ old('address_one', $currentContact->address_one ?? '') }}" required>
                                    @error('address_one')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <input type="text"
                                           class="form-control @error('address_two') is-invalid @enderror"
                                           id="address_two" name="address_two"
                                           value="{{ old('address_two', $currentContact->address_two ?? '') }}"
                                           placeholder="Optional">
                                    <small class="form-text text-muted">OPTIONAL</small>
                                    @error('address_two')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror"
                                           id="city" name="city"
                                           value="{{ old('city', $currentContact->city ?? '') }}" required>
                                    @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="state_province" class="form-label">State/Province</label>
                                    <input type="text"
                                           class="form-control @error('state_province') is-invalid @enderror"
                                           id="state_province" name="state_province"
                                           value="{{ old('state_province', $currentContact->state_province ?? '') }}"
                                           required>
                                    @error('state_province')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="postal_code" class="form-label">ZIP/Postal Code</label>
                                    <input type="text"
                                           class="form-control @error('postal_code') is-invalid @enderror"
                                           id="postal_code" name="postal_code"
                                           value="{{ old('postal_code', $currentContact->postal_code ?? '') }}" required>
                                    @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="country_code" class="form-label">Country</label>
                                    <select name="country_code"
                                            class="form-control @error('country_code') is-invalid @enderror" required>
                                        <option value="">Select a country</option>
                                        @foreach ($countries as $country)
                                            @php $code = \Illuminate\Support\Str::substr($country->iso_code, 0, -1); @endphp
                                            <option value="{{ $code }}"
                                                {{ old('country_code', $currentContact->country_code ?? '') == $code ? 'selected' : '' }}>
                                                {{ $country->name }} ({{ $code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="phone_country" class="form-label">Phone Number</label>
                                            <select class="form-control" id="phone_country" name="phone_country">
                                                <option value="+250"
                                                    {{ old('phone_country', '+250') == '+250' ? 'selected' : '' }}>+250
                                                </option>
                                                <option value="+1"
                                                    {{ old('phone_country') == '+1' ? 'selected' : '' }}>+1</option>
                                                <option value="+44"
                                                    {{ old('phone_country') == '+44' ? 'selected' : '' }}>+44</option>
                                                <option value="+33"
                                                    {{ old('phone_country') == '+33' ? 'selected' : '' }}>+33</option>
                                                <option value="+49"
                                                    {{ old('phone_country') == '+49' ? 'selected' : '' }}>+49</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group mb-3">
                                            <label for="phone" class="form-label">&nbsp;</label>
                                            <input type="text"
                                                   class="form-control @error('phone') is-invalid @enderror"
                                                   id="phone" name="phone"
                                                   value="{{ old('phone', $currentContact ? str_replace(['+250.', '+1.', '+44.', '+33.', '+49.'], '', $currentContact->phone) : '') }}"
                                                   required>
                                            @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="add_phone_extension"
                                               name="add_phone_extension">
                                        <label class="form-check-label" for="add_phone_extension">
                                            Add phone extension
                                        </label>
                                    </div>
                                </div>

                                <div class="row" id="fax_section">
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="fax_country" class="form-label">Fax Number</label>
                                            <select class="form-control" id="fax_country" name="fax_country">
                                                <option value="+1">+1</option>
                                                <option value="+250">+250</option>
                                                <option value="+44">+44</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group mb-3">
                                            <label for="fax_number" class="form-label">&nbsp;</label>
                                            <input type="text"
                                                   class="form-control @error('fax_number') is-invalid @enderror"
                                                   id="fax_number" name="fax_number"
                                                   value="{{ old('fax_number', $currentContact->fax_number ?? '') }}"
                                                   placeholder="Fax Number">
                                            <small class="form-text text-muted">OPTIONAL</small>
                                            @error('fax_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email"
                                           value="{{ old('email', $currentContact->email ?? '') }}" required>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Contact Type Checkboxes -->
                                <div class="form-group mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_for_registrant"
                                               name="use_for_registrant"
                                            {{ $contactType == 'registrant' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="use_for_registrant">
                                            Use for Registrant Contacts
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_for_admin"
                                               name="use_for_admin" {{ $contactType == 'admin' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="use_for_admin">
                                            Use for Administrative Contacts
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_for_technical"
                                               name="use_for_technical"
                                            {{ $contactType == 'technical' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="use_for_technical">
                                            Use for Billing Contacts
                                        </label>
                                    </div>
                                </div>

                                <input type="hidden" name="contact_type" value="{{ $contactType }}">
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-floppy"></i> Save Changes
                                </button>
                                <a href="{{ route('admin.domains.edit', $domain->uuid) }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Current {{ ucfirst($contactType) }} Contact</h3>
                        </div>
                        <div class="card-body">
                            @if ($currentContact)
                                <div class="contact-info">
                                    <p class="mb-2">
                                        <strong>Name:</strong> {{ $currentContact->full_name }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Email:</strong> {{ $currentContact->email }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Phone:</strong> {{ $currentContact->phone }}
                                    </p>
                                    @if ($currentContact->organization)
                                        <p class="mb-2">
                                            <strong>Organization:</strong> {{ $currentContact->organization }}
                                        </p>
                                    @endif
                                    <p class="mb-0">
                                        <strong>Address:</strong><br>
                                        <small class="text-muted">{{ $currentContact->full_address }}</small>
                                    </p>
                                </div>
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-person-x fs-1"></i>
                                    <p class="mb-0">No {{ $contactType }} contact currently assigned</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Contact Type Information</h3>
                        </div>
                        <div class="card-body">
                            @switch($contactType)
                                @case('registrant')
                                    <p class="text-sm">The registrant is the legal owner of the domain name. This contact has
                                        full control over the domain and is responsible for all domain-related decisions.</p>
                                    @break

                                @case('admin')
                                    <p class="text-sm">The administrative contact handles the business aspects of the domain
                                        registration and serves as the primary contact for administrative issues.</p>
                                    @break

                                @case('technical')
                                    <p class="text-sm">The technical contact is responsible for the technical aspects of the
                                        domain, including nameserver changes and DNS management.</p>
                                    @break

                                @case('billing')
                                    <p class="text-sm">The billing contact receives invoices and handles payment-related
                                        matters for the domain registration and renewals.</p>
                                    @break
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const useAddressBookBtn = document.getElementById('useAddressBookBtn');
                const hideAddressBookBtn = document.getElementById('hideAddressBookBtn');
                const addressBookSection = document.getElementById('addressBookSection');
                const contactSelect = document.getElementById('contact_select');
                const isOrganizationCheckbox = document.getElementById('is_organization');
                const organizationField = document.getElementById('organization_field');

                // Toggle address book section
                useAddressBookBtn.addEventListener('click', function() {
                    addressBookSection.style.display = 'block';
                });

                hideAddressBookBtn.addEventListener('click', function() {
                    addressBookSection.style.display = 'none';
                    contactSelect.value = '';
                });

                // Handle organization checkbox
                isOrganizationCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        organizationField.style.display = 'block';
                    } else {
                        organizationField.style.display = 'none';
                        document.getElementById('organization').value = '';
                    }
                });

                // Handle contact selection from address book
                contactSelect.addEventListener('change', function() {
                    if (this.value) {
                        const selectedOption = this.options[this.selectedIndex];

                        // Fill form fields with selected contact data
                        document.getElementById('first_name').value = selectedOption.getAttribute(
                            'data-first-name') || '';
                        document.getElementById('last_name').value = selectedOption.getAttribute(
                            'data-last-name') || '';
                        document.getElementById('organization').value = selectedOption.getAttribute(
                            'data-organization') || '';
                        document.getElementById('address_one').value = selectedOption.getAttribute(
                            'data-address-one') || '';
                        document.getElementById('address_two').value = selectedOption.getAttribute(
                            'data-address-two') || '';
                        document.getElementById('city').value = selectedOption.getAttribute('data-city') || '';
                        document.getElementById('state_province').value = selectedOption.getAttribute(
                            'data-state-province') || '';
                        document.getElementById('postal_code').value = selectedOption.getAttribute(
                            'data-postal-code') || '';
                        document.getElementById('country_code').value = selectedOption.getAttribute(
                            'data-country-code') || '';
                        document.getElementById('email').value = selectedOption.getAttribute('data-email') ||
                            '';

                        // Handle phone number
                        const phone = selectedOption.getAttribute('data-phone') || '';
                        if (phone) {
                            // Extract country code and number
                            const phoneMatch = phone.match(/^\+(\d+)\.(.+)$/);
                            if (phoneMatch) {
                                document.getElementById('phone_country').value = '+' + phoneMatch[1];
                                document.getElementById('phone').value = phoneMatch[2];
                            } else {
                                document.getElementById('phone').value = phone;
                            }
                        }

                        // Handle organization checkbox
                        const organization = selectedOption.getAttribute('data-organization');
                        if (organization) {
                            isOrganizationCheckbox.checked = true;
                            organizationField.style.display = 'block';
                        } else {
                            isOrganizationCheckbox.checked = false;
                            organizationField.style.display = 'none';
                        }

                        // Hide address book section after selection
                        addressBookSection.style.display = 'none';
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
