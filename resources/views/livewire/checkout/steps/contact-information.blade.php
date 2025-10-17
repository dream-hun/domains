<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="mb-0">Contact Information</h4>
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
            <p class="text-muted mb-3">Select a contact for your domain registration:</p>
            
            <div class="row">
                @foreach($this->userContacts as $contact)
                    <div class="col-md-6 mb-3">
                        <div class="contact-card {{ $selectedContactId === $contact->id ? 'selected' : '' }}" 
                             wire:click="selectContact({{ $contact->id }})"
                             style="cursor: pointer;">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="mb-0">
                                            {{ $contact->full_name }}
                                            @if($contact->is_primary)
                                                <span class="badge badge-primary ml-2">Default</span>
                                            @endif
                                        </h5>
                                        @if($selectedContactId === $contact->id)
                                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                        @endif
                                    </div>
                                    <p class="mb-1 text-muted">
                                        <i class="fas fa-envelope mr-2"></i>{{ $contact->email }}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        {{ $contact->city }}, {{ $contact->state_province }}, {{ $contact->country_code }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                <button wire:click="createNewContact" class="btn btn-outline-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Create New Contact
                </button>
            </div>

            <hr class="my-4">

            <div class="custom-control custom-checkbox">
                <input type="checkbox" 
                       class="custom-control-input" 
                       id="useContactForAll" 
                       wire:model="useContactForAll">
                <label class="custom-control-label" for="useContactForAll">
                    Use this contact for all contact types (Registrant, Admin, Tech, Billing)
                </label>
            </div>
        @endif
    </div>
    <div class="card-footer">
        <button wire:click="previousStep" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Back
        </button>
        @if(!$this->userContacts->isEmpty())
            <button wire:click="nextStep" class="btn btn-primary float-right">
                Continue to Payment
                <i class="fas fa-arrow-right ml-2"></i>
            </button>
        @endif
    </div>
</div>

<style>
.contact-card.selected .card {
    border: 2px solid #007bff;
    background-color: #f8f9fa;
}

.contact-card .card {
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.contact-card:hover .card {
    border-color: #007bff;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
</style>
