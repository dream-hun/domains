<x-admin-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Contact</h4>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ route('admin.contacts.update', $contact) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Name Section -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="first_name"
                                            class="form-control @error('first_name') is-invalid @enderror"
                                            value="{{ old('first_name', $contact->first_name) }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="last_name"
                                            class="form-control @error('last_name') is-invalid @enderror"
                                            value="{{ old('last_name', $contact->last_name) }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Title</label>
                                        <input type="text" name="title" id="title"
                                            class="form-control @error('title') is-invalid @enderror"
                                            value="{{ old('title', $contact->title) }}">
                                        @error('title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="organization">Organization</label>
                                        <input type="text" name="organization" id="organization"
                                            class="form-control @error('organization') is-invalid @enderror"
                                            value="{{ old('organization', $contact->organization) }}">
                                        @error('organization')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Address Section -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address_one">Street Address <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="address_one" id="address_one"
                                            class="form-control @error('address_one') is-invalid @enderror"
                                            value="{{ old('address_one', $contact->address_one) }}" required>
                                        @error('address_one')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address_two">Street Address Line 2</label>
                                        <input type="text" name="address_two" id="address_two"
                                            class="form-control @error('address_two') is-invalid @enderror"
                                            value="{{ old('address_two', $contact->address_two) }}">
                                        @error('address_two')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="city">City <span class="text-danger">*</span></label>
                                        <input type="text" name="city" id="city"
                                            class="form-control @error('city') is-invalid @enderror"
                                            value="{{ old('city', $contact->city) }}" required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="state_province">Province/State <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="state_province" id="state_province"
                                            class="form-control @error('state_province') is-invalid @enderror"
                                            value="{{ old('state_province', $contact->state_province) }}" required>
                                        @error('state_province')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="postal_code">Postal Code <span class="text-danger">*</span></label>
                                        <input type="text" name="postal_code" id="postal_code"
                                            class="form-control @error('postal_code') is-invalid @enderror"
                                            value="{{ old('postal_code', $contact->postal_code) }}" required>
                                        @error('postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="country_code">Country <span class="text-danger">*</span></label>
                                        <select name="country_code" id="country_code"
                                            class="form-control select2bs4 @error('country_code') is-invalid @enderror" required>
                                            <option value="">Select Country</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->iso_alpha2 }}"
                                                    {{ old('country_code', $contact->country_code) == $country->iso_alpha2 ? 'selected' : '' }}>
                                                    {{ $country->name }} ({{ $country->iso_alpha2 }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('country_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="phone">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone" id="phone"
                                            class="form-control @error('phone') is-invalid @enderror"
                                            value="{{ old('phone', $contact->phone) }}" required
                                            placeholder="+xx.xxxxxxxxxx">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="phone_extension">Phone Extension</label>
                                        <input type="text" name="phone_extension" id="phone_extension"
                                            class="form-control @error('phone_extension') is-invalid @enderror"
                                            value="{{ old('phone_extension', $contact->phone_extension) }}">
                                        @error('phone_extension')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fax_number">Fax Number</label>
                                        <input type="tel" name="fax_number" id="fax_number"
                                            class="form-control @error('fax_number') is-invalid @enderror"
                                            value="{{ old('fax_number', $contact->fax_number) }}"
                                            placeholder="+xx.xxxxxxxxxx">
                                        @error('fax_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fax_ext">Fax Extension</label>
                                        <input type="text" name="fax_ext" id="fax_ext"
                                            class="form-control @error('fax_ext') is-invalid @enderror"
                                            value="{{ old('fax_ext', $contact->fax_ext) }}">
                                        @error('fax_ext')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="email"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $contact->email) }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_type">Contact Type</label>
                                        <select name="contact_type" id="contact_type"
                                            class="form-control select2bs4 @error('contact_type') is-invalid @enderror">
                                            <option value="">Select Type</option>
                                            @foreach ($contactTypes as $type)
                                                <option value="{{ $type->value }}"
                                                    {{ old('contact_type', $contact->contact_type?->value) == $type->value ? 'selected' : '' }}>
                                                    {{ ucfirst($type->value) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('contact_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="is_primary" id="is_primary"
                                                class="form-check-input @error('is_primary') is-invalid @enderror"
                                                value="1"
                                                {{ old('is_primary', $contact->is_primary) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_primary">
                                                <strong>Mark as Primary Contact</strong>
                                            </label>
                                            <small class="form-text text-muted">Primary contacts appear first in
                                                contact selection lists and are easier to find when registering
                                                domains</small>
                                        </div>
                                        @error('is_primary')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="{{ route('admin.contacts.index') }}"
                                    class="btn btn-secondary mr-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Contact</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

@push('scripts')
<script>
    $(function () {
        $('.select2bs4').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    });
</script>
@endpush
