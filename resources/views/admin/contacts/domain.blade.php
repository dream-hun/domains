<x-admin-layout>
    @section('title', 'Create Domain Contact')
    <div class="container py-4">
        <div class="mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h4 mb-0">Create Contact for Domain Registration</h1>
                        <a href="{{ route('admin.contacts.index') }}" class="text-primary">
                            &larr; Back to Contacts
                        </a>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.contacts.domain.store') }}" method="POST">
                        @csrf

                        <!-- Domain Information -->
                        <div class="bg-light p-3 rounded mb-4">
                            <h5 class="mb-3">Domain Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="domain" class="form-label">
                                        Domain Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="domain" id="domain"
                                           value="{{ old('domain') }}"
                                           class="form-control"
                                           placeholder="example.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_type" class="form-label">
                                        Contact Type <span class="text-danger">*</span>
                                    </label>
                                    <select name="contact_type" id="contact_type" class="form-select select2bs4" required>
                                        <option value="">Select Contact Type</option>
                                        <option
                                            value="registrant" {{ old('contact_type') == 'registrant' ? 'selected' : '' }}>
                                            Registrant
                                        </option>
                                        <option value="admin" {{ old('contact_type') == 'admin' ? 'selected' : '' }}>
                                            Administrative
                                        </option>
                                        <option value="technical" {{ old('contact_type') == 'technical' ? 'selected' : '' }}>
                                            Technical
                                        </option>
                                        <option
                                            value="billing" {{ old('contact_type') == 'billing' ? 'selected' : '' }}>
                                            Billing
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="bg-light p-3 rounded mb-4">
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">
                                        First Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="first_name" id="first_name"
                                           value="{{ old('first_name') }}"
                                           class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">
                                        Last Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="{{ old('last_name') }}"
                                           class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" name="title" id="title"
                                           value="{{ old('title') }}"
                                           class="form-control"
                                           placeholder="Mr., Dr., etc.">
                                </div>
                                <div class="col-md-6">
                                    <label for="organization" class="form-label">Organization</label>
                                    <input type="text" name="organization" id="organization"
                                           value="{{ old('organization') }}"
                                           class="form-control"
                                           placeholder="Company Name">
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="bg-light p-3 rounded mb-4">
                            <h5 class="mb-3">Contact Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        Email Address <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" name="email" id="email"
                                           value="{{ old('email') }}"
                                           class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">
                                        Phone Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" name="phone" id="phone"
                                           value="{{ old('phone') }}"
                                           class="form-control"
                                           placeholder="+250 123 456 789" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone_extension" class="form-label">Phone Extension</label>
                                    <input type="text" name="phone_extension" id="phone_extension"
                                           value="{{ old('phone_extension') }}"
                                           class="form-control" placeholder="Ext. 123">
                                </div>
                                <div class="col-md-6">
                                    <label for="fax_number" class="form-label">Fax Number</label>
                                    <input type="tel" name="fax_number" id="fax_number"
                                           value="{{ old('fax_number') }}"
                                           class="form-control" placeholder="+250 123 456 789">
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="bg-light p-3 rounded mb-4">
                            <h5 class="mb-3">Address Information</h5>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="address_one" class="form-label">
                                        Address Line 1 <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="address_one" id="address_one"
                                           value="{{ old('address_one') }}"
                                           class="form-control"
                                           placeholder="123 Main Street" required>
                                </div>
                                <div class="col-12">
                                    <label for="address_two" class="form-label">Address Line 2</label>
                                    <input type="text" name="address_two" id="address_two"
                                           value="{{ old('address_two') }}"
                                           class="form-control"
                                           placeholder="Suite 100, P.O. Box 123">
                                </div>
                                <div class="col-md-6">
                                    <label for="city" class="form-label">
                                        City <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="city" id="city"
                                           value="{{ old('city') }}"
                                           class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="state_province" class="form-label">
                                        State/Province <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="state_province" id="state_province"
                                           value="{{ old('state_province') }}"
                                           class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="postal_code" class="form-label">
                                        Postal Code <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="postal_code" id="postal_code"
                                           value="{{ old('postal_code') }}"
                                           class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="country_id" class="form-label">Country</label>
                                    <select name="country_id" id="country_id" class="form-select select2bs4">
                                        <option value="">Select Country</option>
                                        @foreach(\App\Models\Country::orderBy('name')->get() as $country)
                                            <option
                                                value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Create Domain Contact
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    @section('scripts')
        @parent
        <script>
            $(function () {
                $('#country_id').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });
                $('#contact_type').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    minimumResultsForSearch: -1
                });
            });
        </script>
    @endsection
</x-admin-layout>




