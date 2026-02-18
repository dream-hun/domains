<div>
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Contact</h5>
                        <button type="button" class="close" wire:click="closeModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('first_name') is-invalid @enderror" 
                                               id="first_name" 
                                               wire:model="first_name">
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('last_name') is-invalid @enderror" 
                                               id="last_name" 
                                               wire:model="last_name">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email <span class="text-danger">*</span></label>
                                        <input type="email" 
                                               class="form-control @error('email') is-invalid @enderror" 
                                               id="email" 
                                               wire:model="email">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" 
                                               wire:model="phone">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="organization">Organization (Optional)</label>
                                <input type="text" 
                                       class="form-control @error('organization') is-invalid @enderror" 
                                       id="organization" 
                                       wire:model="organization">
                                @error('organization')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="address_one">Address Line 1 <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('address_one') is-invalid @enderror" 
                                       id="address_one" 
                                       wire:model="address_one">
                                @error('address_one')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="address_two">Address Line 2 (Optional)</label>
                                <input type="text" 
                                       class="form-control @error('address_two') is-invalid @enderror" 
                                       id="address_two" 
                                       wire:model="address_two">
                                @error('address_two')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('city') is-invalid @enderror" 
                                               id="city" 
                                               wire:model="city">
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state_province">State/Province <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('state_province') is-invalid @enderror" 
                                               id="state_province" 
                                               wire:model="state_province">
                                        @error('state_province')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="postal_code">Postal Code <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control @error('postal_code') is-invalid @enderror" 
                                               id="postal_code" 
                                               wire:model="postal_code">
                                        @error('postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="country_code">Country <span class="text-danger">*</span></label>
                                        <select class="form-control @error('country_code') is-invalid @enderror"
                                                id="country_code"
                                                wire:model="country_code">
                                            <option value="">Select a country</option>
                                            @foreach ($this->countries as $country)
                                                <option value="{{ $country->iso_alpha2 }}">
                                                    {{ $country->name }} ({{ $country->iso_alpha2 }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('country_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="is_primary" 
                                       wire:model="is_primary">
                                <label class="custom-control-label" for="is_primary">
                                    Set as default contact
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Save Contact
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
