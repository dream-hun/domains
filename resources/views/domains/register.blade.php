@php use App\Enums\DomainType; @endphp
<x-admin-layout>
    @section('page-title')
        Check out
    @endsection
    <div class="container-fluid">
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('domains.register.store') }}" method="POST" id="domainRegistrationForm"
            class="needs-validation" novalidate>

            @csrf
            <input type="hidden" name="domain_name" value="{{ $cartItems->first()->name ?? '' }}" required>
            <input type="hidden" name="registration_years" value="{{ $cartItems->first()->quantity ?? 1 }}">
            @error('domain_name')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
            <div id="domainRegistration">
                <div class="row">
                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Domain Contacts</h3>
                            </div>
                            <div class="card-body">
                                <div x-data="{
                                    useSingleContact: {{ old('use_single_contact', '0') === '1' ? 'true' : 'false' }},
                                    registrantContactId: '{{ old('registrant_contact_id', '') }}'
                                }">
                                    <div class="form-check form-check-inline mb-3">
                                        <!-- Hidden field to ensure a value is always sent -->
                                        <input type="hidden" name="use_single_contact" value="0">
                                        <input type="checkbox" class="form-check-input" id="use_single_contact"
                                            name="use_single_contact" value="1" x-model="useSingleContact"
                                            {{ old('use_single_contact') ? 'checked' : '' }}>
                                        <label class="form-check-label ms-2" for="use_single_contact">
                                            Use the same contact for all roles (Registrant, Admin, Technical, Billing)
                                        </label>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group mb-3" x-data="{ contact: { id: '{{ old('registrant_contact_id', '') }}', details: null } }" x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }"
                                                <label class="form-label font-weight-bold">Registrant Contact <span
                                                    class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select name="registrant_contact_id"
                                                        class="form-control @error('registrant_contact_id') is-invalid @enderror"
                                                        x-model="contact.id"
                                                        x-on:change="registrantContactId = $el.value; fetchContactDetails($el.value).then(result => contact.details = result)"
                                                        required>
                                                        <option value="">Select Registrant Contact</option>
                                                        @foreach ($existingContacts['registrant'] ?? [] as $contact)
                                                            <option value="{{ $contact->id }}"
                                                                {{ old('registrant_contact_id') == $contact->id ? 'selected' : '' }}>
                                                                {{ $contact->full_name }}
                                                                ({{ $contact->email }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append">
                                                        <a href="{{ route('admin.contacts.create') }}"
                                                            class="btn btn-primary add-contact-btn">
                                                            <i class="bi bi-plus-lg"></i> Add New
                                                        </a>
                                                    </div>
                                                </div>
                                                @error('registrant_contact_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <template x-if="contact.details">
                                                    <div class="contact-details mt-3">
                                                        <div class="card">
                                                            <div class="card-header bg-light">
                                                                <h5 class="mb-0">Contact Details</h5>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Name:</strong> <span
                                                                                x-text="contact.details.full_name"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Email:</strong> <span
                                                                                x-text="contact.details.email"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Phone:</strong> <span
                                                                                x-text="contact.details.phone || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Organization:</strong>
                                                                            <span
                                                                                x-text="contact.details.organization || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Address:</strong>
                                                                            <span
                                                                                x-text="contact.details.address_one || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>City:</strong> <span
                                                                                x-text="contact.details.city || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Province:</strong>
                                                                            <span
                                                                                x-text="contact.details.province || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Country:</strong>
                                                                            <span
                                                                                x-text="contact.details.country_code || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Postal Code:</strong>
                                                                            <span
                                                                                x-text="contact.details.postal_code || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="col-md-3" x-show="!useSingleContact" x-cloak>
                                            <div class="form-group mb-3" x-data="{ contact: { id: '{{ old('admin_contact_id', '') }}', details: null } }"
                                                x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }">
                                                <label class="form-label font-weight-bold">Admin Contact <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select name="admin_contact_id"
                                                        class="form-control @error('admin_contact_id') is-invalid @enderror"
                                                        x-model="contact.id"
                                                        @change="fetchContactDetails($el.value).then(result => contact.details = result)"
                                                        :required="!useSingleContact">
                                                        <option value="">Select Admin Contact</option>
                                                        @foreach ($existingContacts['admin'] ?? [] as $contact)
                                                            <option value="{{ $contact->id }}"
                                                                {{ old('admin_contact_id') == $contact->id ? 'selected' : '' }}>
                                                                {{ $contact->full_name }}
                                                                ({{ $contact->email }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append">
                                                        <a href="{{ route('admin.contacts.create') }}"
                                                            class="btn btn-primary add-contact-btn">
                                                            <i class="bi bi-plus-lg"></i> Add New
                                                        </a>
                                                    </div>
                                                </div>
                                                @error('admin_contact_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <template x-if="contact.details">
                                                    <div class="contact-details mt-3">
                                                        <div class="card">
                                                            <div class="card-header bg-light">
                                                                <h5 class="mb-0">Contact Details</h5>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Name:</strong> <span
                                                                                x-text="contact.details.full_name"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Email:</strong> <span
                                                                                x-text="contact.details.email"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Phone:</strong> <span
                                                                                x-text="contact.details.phone || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Organization:</strong>
                                                                            <span
                                                                                x-text="contact.details.organization || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Address:</strong>
                                                                            <span
                                                                                x-text="contact.details.address_one || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>City:</strong> <span
                                                                                x-text="contact.details.city || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Province:</strong>
                                                                            <span
                                                                                x-text="contact.details.province || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Country:</strong>
                                                                            <span
                                                                                x-text="contact.details.country_code || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Postal Code:</strong>
                                                                            <span
                                                                                x-text="contact.details.postal_code || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="col-md-3" x-show="!useSingleContact" x-cloak>
                                            <div class="form-group mb-3" x-data="{ contact: { id: '{{ old('tech_contact_id', '') }}', details: null } }"
                                                x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }">
                                                <label class="form-label font-weight-bold">Technical Contact <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select name="tech_contact_id"
                                                        class="form-control @error('tech_contact_id') is-invalid @enderror"
                                                        x-model="contact.id"
                                                        @change="fetchContactDetails($el.value).then(result => contact.details = result)"
                                                        :required="!useSingleContact">
                                                        <option value="">Select Technical Contact</option>
                                                        @foreach ($existingContacts['technical'] ?? [] as $contact)
                                                            <option value="{{ $contact->id }}"
                                                                {{ old('tech_contact_id') == $contact->id ? 'selected' : '' }}>
                                                                {{ $contact->full_name }}
                                                                ({{ $contact->email }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append">
                                                        <a href="{{ route('admin.contacts.create') }}"
                                                            class="btn btn-primary add-contact-btn">
                                                            <i class="bi bi-plus-lg"></i> Add New
                                                        </a>
                                                    </div>
                                                </div>
                                                @error('tech_contact_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <template x-if="contact.details">
                                                    <div class="contact-details mt-3">
                                                        <div class="card">
                                                            <div class="card-header bg-light">
                                                                <h5 class="mb-0">Contact Details</h5>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Name:</strong> <span
                                                                                x-text="contact.details.full_name"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Email:</strong> <span
                                                                                x-text="contact.details.email"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Phone:</strong> <span
                                                                                x-text="contact.details.phone || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Organization:</strong>
                                                                            <span
                                                                                x-text="contact.details.organization || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Address:</strong>
                                                                            <span
                                                                                x-text="contact.details.address_one || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>City:</strong> <span
                                                                                x-text="contact.details.city || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Province:</strong>
                                                                            <span
                                                                                x-text="contact.details.province || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Country:</strong>
                                                                            <span
                                                                                x-text="contact.details.country_code || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Postal Code:</strong>
                                                                            <span
                                                                                x-text="contact.details.postal_code || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="col-md-3" x-show="!useSingleContact" x-cloak>
                                            <div class="form-group mb-3" x-data="{ contact: { id: '{{ old('billing_contact_id', '') }}', details: null } }"
                                                x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }">
                                                <label class="form-label font-weight-bold">Billing Contact <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select name="billing_contact_id"
                                                        class="form-control @error('billing_contact_id') is-invalid @enderror"
                                                        x-model="contact.id"
                                                        @change="fetchContactDetails($el.value).then(result => contact.details = result)"
                                                        :required="!useSingleContact">
                                                        <option value="">Select Billing Contact</option>
                                                        @foreach ($existingContacts['billing'] ?? [] as $contact)
                                                            <option value="{{ $contact->id }}"
                                                                {{ old('billing_contact_id') == $contact->id ? 'selected' : '' }}>
                                                                {{ $contact->full_name }}
                                                                ({{ $contact->email }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append">
                                                        <a href="{{ route('admin.contacts.create') }}"
                                                            class="btn btn-primary add-contact-btn">
                                                            <i class="bi bi-plus-lg"></i> Add New
                                                        </a>
                                                    </div>
                                                </div>
                                                @error('billing_contact_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <template x-if="contact.details">
                                                    <div class="contact-details mt-3">
                                                        <div class="card">
                                                            <div class="card-header bg-light">
                                                                <h5 class="mb-0">Contact Details</h5>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Name:</strong> <span
                                                                                x-text="contact.details.full_name"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Email:</strong> <span
                                                                                x-text="contact.details.email"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Phone:</strong> <span
                                                                                x-text="contact.details.phone || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Organization:</strong>
                                                                            <span
                                                                                x-text="contact.details.organization || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1"><strong>Address:</strong>
                                                                            <span
                                                                                x-text="contact.details.address_one || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>City:</strong> <span
                                                                                x-text="contact.details.city || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Province:</strong>
                                                                            <span
                                                                                x-text="contact.details.province || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Country:</strong>
                                                                            <span
                                                                                x-text="contact.details.country_code || 'N/A'"></span>
                                                                        </p>
                                                                        <p class="mb-1"><strong>Postal
                                                                                Code:</strong>
                                                                            <span
                                                                                x-text="contact.details.postal_code || 'N/A'"></span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <template x-if="useSingleContact && registrantContactId">
                                            <div>
                                                <input type="hidden" name="admin_contact_id"
                                                    :value="registrantContactId">
                                                <input type="hidden" name="tech_contact_id"
                                                    :value="registrantContactId">
                                                <input type="hidden" name="billing_contact_id"
                                                    :value="registrantContactId">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card" x-data="{ disableDNS: {{ old('disable_dns', '0') === '1' ? 'true' : 'false' }} }">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="card-title mb-0">Name Servers <small class="text-muted">(Minimum 2,
                                            Maximum 4)</small></h3>
                                </div>
                            </div>
                            @error('nameservers')
                                <div class="alert alert-danger mx-3 mt-3 mb-0">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="card-body">
                                <div class="form-check form-check-inline mb-3">
                                    <!-- Hidden field to ensure a value is always sent -->
                                    <input type="hidden" name="disable_dns" value="0">
                                    <input type="checkbox" class="form-check-input" id="disable_dns"
                                        name="disable_dns" value="1" x-model="disableDNS"
                                        {{ old('disable_dns') ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="disable_dns">
                                        Don't delegate this domain now
                                    </label>
                                </div>

                                <div class="row">
                                    @for ($i = 0; $i < 4; $i++)
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label class="form-label font-weight-bold">
                                                    Name Server {{ $i + 1 }}
                                                    @if ($i < 2)
                                                        <span class="text-danger" x-show="!disableDNS"
                                                            x-cloak>*</span>
                                                    @endif
                                                </label>
                                                <input type="text" name="nameservers[{{ $i }}]"
                                                    class="form-control @error('nameservers.' . $i) is-invalid @enderror"
                                                    placeholder="ns{{ $i + 1 }}.example.com"
                                                    value="{{ old('nameservers.' . $i) }}"
                                                    :required="!disableDNS && {{ $i < 2 ? 'true' : 'false' }}"
                                                    :readonly="disableDNS" :disabled="disableDNS">
                                                @error('nameservers.' . $i)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endfor

                                    <div class="col-12" x-show="disableDNS" x-cloak>
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle"></i> The domain will use the registry's
                                            default
                                            name servers.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body box-profile">
                                <h3 class="profile-username text-center">Cart Summary</h3>

                                <ul class="list-group list-group-unbordered mb-3">
                                    @php
                                        $currency = $domainType === DomainType::Local ? 'RWF' : 'USD';
                                    @endphp

                                    @foreach ($cartItems as $item)
                                        <li class="list-group-item">
                                            {{ $item->name }}
                                            <p class="float-right">
                                                {{ money($item->price * $item->quantity, $currency) }}
                                                /
                                                {{ $item->quantity }} {{ Str::plural('Year', $item->quantity) }}
                                            </p>
                                        </li>
                                    @endforeach

                                    <li class="list-group list-group-item">
                                        <b>Total</b>
                                        <b>
                                            <p class="float-right">
                                                {{ money($cartTotal, $currency) }}
                                            </p>
                                        </b>
                                    </li>
                                </ul>


                                <a href="{{ route('cart.index') }}" class="btn btn-secondary btn-block mb-3">
                                    <i class="bi bi-arrow-left"></i> Back to Cart
                                </a>
                                @if ($domainType === DomainType::International)
                                    <div class="alert alert-info mt-3 mb-3">
                                        <i class="bi bi-info-circle"></i> Free WHOIS Privacy included with your
                                        registration.
                                    </div>
                                @endif

                                <!-- Terms and Privacy Policy -->
                                <div class="form-check mb-3">
                                    <input type="checkbox"
                                        class="form-check-input @error('terms_accepted') is-invalid @enderror"
                                        id="terms_accepted" name="terms_accepted" required
                                        {{ old('terms_accepted') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="terms_accepted">
                                        I accept the <a href="#" target="_blank">Terms and Conditions</a> <span
                                            class="text-danger">*</span>
                                    </label>
                                    @error('terms_accepted')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox"
                                        class="form-check-input @error('privacy_policy_accepted') is-invalid @enderror"
                                        id="privacy_policy_accepted" name="privacy_policy_accepted" required
                                        {{ old('privacy_policy_accepted') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="privacy_policy_accepted">
                                        I accept the <a href="#" target="_blank">Privacy Policy</a> <span
                                            class="text-danger">*</span>
                                    </label>
                                    @error('privacy_policy_accepted')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary btn-block" id="registerDomainBtn">
                                    <i class="bi bi-check-circle"></i> Register Domain
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>

</x-admin-layout>
