<x-admin-layout>
    <div class="container col-md-12 py-5">
        <div class="card shadow-sm">
            <h2 class="card-header">Contact Information</h2>
            <div class="card-body">
                <form id="contactForm" method="POST" action="{{ route('admin.contacts.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name"
                            class="form-control @error('first_name') is-invalid @enderror"
                            value="{{ old('first_name') }}" required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Last Name</label>
                        <input type="text" name="last_name"
                            class="form-control @error('last_name') is-invalid @enderror"
                            value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title"
                            class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Organization</label>
                        <input type="text" name="organization"
                            class="form-control @error('organization') is-invalid @enderror"
                            value="{{ old('organization') }}">
                        @error('organization')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Address Line 1</label>
                        <input type="text" name="address_one"
                            class="form-control @error('address_one') is-invalid @enderror"
                            value="{{ old('address_one') }}" required>
                        @error('address_one')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address Line 2 (Optional)</label>
                        <input type="text" name="address_two"
                            class="form-control @error('address_two') is-invalid @enderror"
                            value="{{ old('address_two') }}">
                        @error('address_two')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">City</label>
                        <input type="text" name="city"
                            class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}"
                            required>
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">State/Province</label>
                        <input type="text" name="state_province"
                            class="form-control @error('state_province') is-invalid @enderror"
                            value="{{ old('state_province') }}" required>
                        @error('state_province')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Postal Code</label>
                        <input type="text" name="postal_code"
                            class="form-control @error('postal_code') is-invalid @enderror"
                            value="{{ old('postal_code') }}" required>
                        @error('postal_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Country</label>
                        <select name="country_code"
                            class="form-control select2bs4 @error('country_code') is-invalid @enderror" required>
                            <option value="">Select a country</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->iso_alpha2 }}"
                                    {{ old('country_code') == $country->iso_alpha2 ? 'selected' : '' }}>
                                    {{ $country->name }} ({{ $country->iso_alpha2 }})
                                </option>
                            @endforeach
                        </select>
                        @error('country_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Phone</label>
                        <input type="tel" name="phone"
                            class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}"
                            required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Extension</label>
                        <input type="text" name="phone_extension"
                            class="form-control @error('phone_extension') is-invalid @enderror"
                            value="{{ old('phone_extension') }}">
                        @error('phone_extension')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fax Number</label>
                        <input type="tel" name="fax_number"
                            class="form-control @error('fax_number') is-invalid @enderror"
                            value="{{ old('fax_number') }}">
                        @error('fax_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                            required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" name="is_primary" id="is_primary"
                            class="form-check-input @error('is_primary') is-invalid @enderror" value="1"
                            {{ old('is_primary') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_primary">
                            <strong>Mark as Primary Contact</strong>
                        </label>
                        <small class="form-text text-muted d-block">Primary contacts appear first in contact selection
                            lists and are easier to find when registering domains</small>
                        @error('is_primary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Create Contact</button>
                </form>
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
