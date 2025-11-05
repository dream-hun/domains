<div class="card shadow-sm" x-data="{ useSingleContact: false }">
    <div class="card-header">
        <h4 class="mb-0">Contact Information</h4>
        <p class="text-muted mb-0 mt-1 small">Select contacts for domain registration roles</p>
    </div>
    <div class="card-body">
        @if($this->userContacts->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                You don't have any saved contacts yet. Please create a contact to continue.
            </div>
            <button wire:click="createNewContact" class="btn btn-primary btn-lg">
                <i class="fas fa-plus mr-2"></i>
                Create New Contact
            </button>
        @else
            {{-- Quick Contact Selection --}}
            <div class="mb-4 p-3 bg-light border rounded">
                <h5 class="mb-3">
                    <i class="fas fa-bolt mr-2 text-primary"></i>
                    Quick Selection
                </h5>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="use_single_contact" x-model="useSingleContact">
                    <label class="form-check-label" for="use_single_contact">
                        Use the same contact for all roles
                    </label>
                </div>
                <div class="row align-items-end" x-show="useSingleContact" x-cloak>
                    <div class="col-md-8">
                        <label for="contactSelect" class="form-label">Select a contact to use for all roles</label>
                        <select id="contactSelect" 
                                class="form-control form-control-lg" 
                                wire:model.live="quickSelectContactId">
                            <option value="">-- Choose a contact --</option>
                            @foreach($this->userContacts as $contact)
                                <option value="{{ $contact->id }}">
                                    {{ $contact->full_name }} - {{ $contact->email }}
                                    @if($contact->is_primary) (Default) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button wire:click="createNewContact" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-plus mr-2"></i>
                            Add New Contact
                        </button>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            {{-- Domain Contacts --}}
            <div class="row">
                {{-- Registrant Contact --}}
                <div class="col-md-6 mb-4">
                    <div class="form-group mb-3" x-data="{ contact: { id: @entangle('selectedRegistrantId'), details: null } }" 
                         x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }">
                        <label class="form-label font-weight-bold">
                            Registrant Contact 
                            <span class="text-danger">*</span>
                            <small class="text-muted">(Domain Owner)</small>
                        </label>
                        <div class="input-group">
                            <select 
                                wire:model.live="selectedRegistrantId"
                                class="form-control"
                                x-model="contact.id"
                                @change="fetchContactDetails($el.value).then(result => contact.details = result)"
                                required>
                                <option value="">Select Registrant Contact</option>
                                @foreach($this->userContacts as $userContact)
                                    <option value="{{ $userContact->id }}">
                                        {{ $userContact->full_name }} ({{ $userContact->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button wire:click="createNewContact" class="btn btn-primary" type="button">
                                    <i class="bi bi-plus-lg"></i> Add New
                                </button>
                            </div>
                        </div>
                        <template x-if="contact.details">
                            <div class="contact-details mt-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Contact Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <p class="mb-1"><strong>Name:</strong> <span x-text="contact.details.full_name"></span></p>
                                                <p class="mb-1"><strong>Email:</strong> <span x-text="contact.details.email"></span></p>
                                                <p class="mb-1"><strong>Phone:</strong> <span x-text="contact.details.phone || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Organization:</strong> <span x-text="contact.details.organization || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Address:</strong> <span x-text="contact.details.address_one || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>City:</strong> <span x-text="contact.details.city || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Province:</strong> <span x-text="contact.details.state_province || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Country:</strong> <span x-text="contact.details.country_code || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Postal Code:</strong> <span x-text="contact.details.postal_code || 'N/A'"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Admin Contact --}}
                <div class="col-md-6 mb-4" x-show="!useSingleContact" x-cloak>
                    <div class="form-group mb-3" x-data="{ contact: { id: @entangle('selectedAdminId'), details: null } }"
                         x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }">
                        <label class="form-label font-weight-bold">
                            Admin Contact 
                            <span class="text-danger" x-show="!useSingleContact">*</span>
                        </label>
                        <div class="input-group">
                            <select 
                                wire:model.live="selectedAdminId"
                                class="form-control"
                                x-model="contact.id"
                                @change="fetchContactDetails($el.value).then(result => contact.details = result)"
                                :required="!useSingleContact">
                                <option value="">Select Admin Contact</option>
                                @foreach($this->userContacts as $userContact)
                                    <option value="{{ $userContact->id }}">
                                        {{ $userContact->full_name }} ({{ $userContact->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button wire:click="createNewContact" class="btn btn-primary" type="button">
                                    <i class="bi bi-plus-lg"></i> Add New
                                </button>
                            </div>
                        </div>
                        <template x-if="contact.details">
                            <div class="contact-details mt-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Contact Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <p class="mb-1"><strong>Name:</strong> <span x-text="contact.details.full_name"></span></p>
                                                <p class="mb-1"><strong>Email:</strong> <span x-text="contact.details.email"></span></p>
                                                <p class="mb-1"><strong>Phone:</strong> <span x-text="contact.details.phone || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Organization:</strong> <span x-text="contact.details.organization || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Address:</strong> <span x-text="contact.details.address_one || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>City:</strong> <span x-text="contact.details.city || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Province:</strong> <span x-text="contact.details.state_province || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Country:</strong> <span x-text="contact.details.country_code || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Postal Code:</strong> <span x-text="contact.details.postal_code || 'N/A'"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Technical Contact --}}
                <div class="col-md-6 mb-4" x-show="!useSingleContact" x-cloak>
                    <div class="form-group mb-3" x-data="{ contact: { id: @entangle('selectedTechId'), details: null } }"
                         x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }">
                        <label class="form-label font-weight-bold">
                            Technical Contact 
                            <span class="text-danger" x-show="!useSingleContact">*</span>
                        </label>
                        <div class="input-group">
                            <select 
                                wire:model.live="selectedTechId"
                                class="form-control"
                                x-model="contact.id"
                                @change="fetchContactDetails($el.value).then(result => contact.details = result)"
                                :required="!useSingleContact">
                                <option value="">Select Technical Contact</option>
                                @foreach($this->userContacts as $userContact)
                                    <option value="{{ $userContact->id }}">
                                        {{ $userContact->full_name }} ({{ $userContact->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button wire:click="createNewContact" class="btn btn-primary" type="button">
                                    <i class="bi bi-plus-lg"></i> Add New
                                </button>
                            </div>
                        </div>
                        <template x-if="contact.details">
                            <div class="contact-details mt-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Contact Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <p class="mb-1"><strong>Name:</strong> <span x-text="contact.details.full_name"></span></p>
                                                <p class="mb-1"><strong>Email:</strong> <span x-text="contact.details.email"></span></p>
                                                <p class="mb-1"><strong>Phone:</strong> <span x-text="contact.details.phone || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Organization:</strong> <span x-text="contact.details.organization || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Address:</strong> <span x-text="contact.details.address_one || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>City:</strong> <span x-text="contact.details.city || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Province:</strong> <span x-text="contact.details.state_province || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Country:</strong> <span x-text="contact.details.country_code || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Postal Code:</strong> <span x-text="contact.details.postal_code || 'N/A'"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Billing Contact --}}
                <div class="col-md-6 mb-4" x-show="!useSingleContact" x-cloak>
                    <div class="form-group mb-3" x-data="{ contact: { id: @entangle('selectedBillingId'), details: null } }"
                         x-init="if (contact.id) { fetchContactDetails(contact.id).then(result => contact.details = result) }">
                        <label class="form-label font-weight-bold">
                            Billing Contact 
                            <span class="text-danger" x-show="!useSingleContact">*</span>
                        </label>
                        <div class="input-group">
                            <select 
                                wire:model.live="selectedBillingId"
                                class="form-control"
                                x-model="contact.id"
                                @change="fetchContactDetails($el.value).then(result => contact.details = result)"
                                :required="!useSingleContact">
                                <option value="">Select Billing Contact</option>
                                @foreach($this->userContacts as $userContact)
                                    <option value="{{ $userContact->id }}">
                                        {{ $userContact->full_name }} ({{ $userContact->email }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button wire:click="createNewContact" class="btn btn-primary" type="button">
                                    <i class="bi bi-plus-lg"></i> Add New
                                </button>
                            </div>
                        </div>
                        <template x-if="contact.details">
                            <div class="contact-details mt-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Contact Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12">
                                                <p class="mb-1"><strong>Name:</strong> <span x-text="contact.details.full_name"></span></p>
                                                <p class="mb-1"><strong>Email:</strong> <span x-text="contact.details.email"></span></p>
                                                <p class="mb-1"><strong>Phone:</strong> <span x-text="contact.details.phone || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Organization:</strong> <span x-text="contact.details.organization || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Address:</strong> <span x-text="contact.details.address_one || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>City:</strong> <span x-text="contact.details.city || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Province:</strong> <span x-text="contact.details.state_province || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Country:</strong> <span x-text="contact.details.country_code || 'N/A'"></span></p>
                                                <p class="mb-1"><strong>Postal Code:</strong> <span x-text="contact.details.postal_code || 'N/A'"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="card-footer">
        <button wire:click="previousStep" class="btn btn-secondary">
            <i class="bi bi-arrow-left mr-2"></i>
            Back
        </button>
        @if(!$this->userContacts->isEmpty())
            <button wire:click="nextStep" class="btn btn-primary float-right">
                Continue to Payment
                <i class="bi bi-arrow-right ml-2"></i>
            </button>
        @endif
    </div>
</div>

<script>
    // Global function to fetch contact details
    window.fetchContactDetails = async function(contactId) {
        if (!contactId) return null;
        
        try {
            const response = await fetch(`/api/contacts/${contactId}`);
            const data = await response.json();
            
            if (data.success && data.contact) {
                return data.contact;
            }
            
            return null;
        } catch (error) {
            console.error('Error fetching contact details:', error);
            return null;
        }
    };
</script>

<style>
.contact-details .card {
    border: 1px solid #dee2e6;
}

.contact-details .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.contact-details p {
    font-size: 0.875rem;
}

[x-cloak] {
    display: none !important;
}
</style>
