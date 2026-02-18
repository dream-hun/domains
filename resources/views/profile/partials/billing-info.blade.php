<div class="card shadow-md" style=" margin-left: 1.2rem; margin-right:1.2rem;">
    <div class="py-4">
         @if (session('billing_status') === 'success')
                <div class="alert alert-success">
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                       class="text-sm text-green-600 font-medium">{{ session('billing_message') }}</p>
                </div>
                @endif
                
                @if (session('billing_status') === 'error')
                <div class="alert alert-danger">
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)"
                       class="text-sm text-red-600 font-medium">{{ session('billing_message') }}</p>
                </div>
                @endif
                
            
    </div>
    <div class="card-header" style="border: none !important;">
        <h3 class="h4">Update Billing Information</h3></div>
    <div class="card-body">
        <form method="post" action="{{ route('address.update') }}" class="mt-6 space-y-6">
            @csrf
            <div class="form-group">
                <label for="full_name" class="form-label required">Full Name</label>
                <input id="full_name" type="text" name="full_name"
                       class="form-control @error('full_name') is-invalid @enderror"
                       value="{{ old('full_name', $user->address?->full_name ?? $user->name) }}" required autofocus>
                @error('full_name')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label required">Email</label>
                <input id="email" type="email" name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="company" class="form-label">Company (Optional)</label>
                <input id="company" type="text" name="company"
                       class="form-control @error('company') is-invalid @enderror"
                       value="{{ old('company', $user->address?->company) }}">
                @error('company')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="phone_number" class="form-label required">Phone Number</label>
                <input id="phone_number" type="text" name="phone_number"
                       class="form-control @error('phone_number') is-invalid @enderror"
                       value="{{ old('phone_number', $user->address?->phone_number) }}">
                @error('phone_number')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="address_line_one" class="form-label required">Address Line 1</label>
                <input id="address_line_one" type="text" name="address_line_one"
                       class="form-control @error('address_line_one') is-invalid @enderror"
                       value="{{ old('address_line_one', $user->address?->address_line_one) }}">
                @error('address_line_one')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="address_line_two" class="form-label">Address Line 2 (Optional)</label>
                <input id="address_line_two" type="text" name="address_line_two"
                       class="form-control @error('address_line_two') is-invalid @enderror"
                       value="{{ old('address_line_two', $user->address?->address_line_two) }}">
                @error('address_line_two')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="row">
                <div class="form-group col-6">
                    <label for="city" class="form-label required">City</label>
                    <input id="city" type="text" name="city"
                           class="form-control @error('city') is-invalid @enderror"
                           value="{{ old('city', $user->address?->city) }}">
                    @error('city')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-6">
                    <label for="state" class="form-label required">State</label>
                    <input id="state" type="text" name="state"
                           class="form-control @error('state') is-invalid @enderror"
                           value="{{ old('state', $user->address?->state) }}">
                    @error('state')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="row">
                <div class="form-group col-6">
                    <label for="postal_code" class="form-label">Postal Code </label>
                    <input id="postal_code" type="text" name="postal_code"
                           class="form-control @error('postal_code') is-invalid @enderror"
                           value="{{ old('postal_code', $user->address?->postal_code) }}">
                    @error('postal_code')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-6">
                    <label class="form-label required">Country</label>
                    <select name="country_code"
                            class="form-control py-2 @error('country_code') is-invalid @enderror" required>
                        <option value="">Select a country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->iso_alpha2 }}"
                                {{ old('country_code', $user->address?->country_code) == $country->iso_alpha2 ? 'selected' : '' }}>
                                {{ $country->name }} ({{ $country->iso_alpha2 }})
                            </option>
                        @endforeach
                    </select>
                    @error('country_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Update Billing') }}</x-primary-button>
                
               
        </form>
    </div>
</div>
