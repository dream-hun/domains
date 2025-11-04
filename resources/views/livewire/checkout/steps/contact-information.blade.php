<div class="card shadow-sm">
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
                <div class="row align-items-end">
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
                <p class="text-muted small mb-0 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Or customize each role individually below
                </p>
            </div>

            <hr class="my-4">

            {{-- Registrant Contact --}}
            <div class="contact-section mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-user mr-2 text-primary"></i>
                    Registrant Contact
                    <small class="text-muted">(Domain Owner)</small>
                </h5>
                <div class="row">
                    @foreach($this->userContacts as $contact)
                        <div class="col-md-6 mb-3">
                            <div class="contact-card {{ $selectedRegistrantId === $contact->id ? 'selected' : '' }}"
                                 role="button"
                                 tabindex="0"
                                 style="cursor: pointer;">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">
                                                {{ $contact->full_name }}
                                                @if($contact->is_primary)
                                                    <span class="badge badge-primary ml-2">Default</span>
                                                @endif
                                            </h6>
                                            @if($selectedRegistrantId === $contact->id)
                                                <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                            @endif
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-envelope mr-2"></i>{{ $contact->email }}
                                        </p>
                                        <p class="mb-2 text-muted small">
                                            <i class="fas fa-map-marker-alt mr-2"></i>
                                            {{ $contact->city }}, {{ $contact->country_code }}
                                        </p>
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <button wire:click="selectRegistrant({{ $contact->id }})" 
                                                    class="btn btn-outline-primary {{ $selectedRegistrantId === $contact->id ? 'active' : '' }}">
                                                Select for Registrant
                                            </button>
                                            <button wire:click="useContactForAll({{ $contact->id }})" 
                                                    class="btn btn-outline-success">
                                                Use for All
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr class="my-4">

            {{-- Admin Contact --}}
            <div class="contact-section mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-user-shield mr-2 text-info"></i>
                    Administrative Contact
                </h5>
                <div class="row">
                    @foreach($this->userContacts as $contact)
                        <div class="col-md-6 mb-3">
                            <div class="contact-card {{ $selectedAdminId === $contact->id ? 'selected' : '' }}"
                                 role="button"
                                 tabindex="0"
                                 style="cursor: pointer;">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">
                                                {{ $contact->full_name }}
                                                @if($contact->is_primary)
                                                    <span class="badge badge-primary ml-2">Default</span>
                                                @endif
                                            </h6>
                                            @if($selectedAdminId === $contact->id)
                                                <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                            @endif
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-envelope mr-2"></i>{{ $contact->email }}
                                        </p>
                                        <p class="mb-2 text-muted small">
                                            <i class="fas fa-map-marker-alt mr-2"></i>
                                            {{ $contact->city }}, {{ $contact->country_code }}
                                        </p>
                                        <button wire:click="selectAdmin({{ $contact->id }})" 
                                                class="btn btn-outline-info btn-sm w-100 {{ $selectedAdminId === $contact->id ? 'active' : '' }}">
                                            Select for Admin
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr class="my-4">

            {{-- Technical Contact --}}
            <div class="contact-section mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-tools mr-2 text-warning"></i>
                    Technical Contact
                </h5>
                <div class="row">
                    @foreach($this->userContacts as $contact)
                        <div class="col-md-6 mb-3">
                            <div class="contact-card {{ $selectedTechId === $contact->id ? 'selected' : '' }}"
                                 role="button"
                                 tabindex="0"
                                 style="cursor: pointer;">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">
                                                {{ $contact->full_name }}
                                                @if($contact->is_primary)
                                                    <span class="badge badge-primary ml-2">Default</span>
                                                @endif
                                            </h6>
                                            @if($selectedTechId === $contact->id)
                                                <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                            @endif
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-envelope mr-2"></i>{{ $contact->email }}
                                        </p>
                                        <p class="mb-2 text-muted small">
                                            <i class="fas fa-map-marker-alt mr-2"></i>
                                            {{ $contact->city }}, {{ $contact->country_code }}
                                        </p>
                                        <button wire:click="selectTech({{ $contact->id }})" 
                                                class="btn btn-outline-warning btn-sm w-100 {{ $selectedTechId === $contact->id ? 'active' : '' }}">
                                            Select for Technical
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr class="my-4">

            {{-- Billing Contact --}}
            <div class="contact-section mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-credit-card mr-2 text-success"></i>
                    Billing Contact
                </h5>
                <div class="row">
                    @foreach($this->userContacts as $contact)
                        <div class="col-md-6 mb-3">
                            <div class="contact-card {{ $selectedBillingId === $contact->id ? 'selected' : '' }}"
                                 role="button"
                                 tabindex="0"
                                 style="cursor: pointer;">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">
                                                {{ $contact->full_name }}
                                                @if($contact->is_primary)
                                                    <span class="badge badge-primary ml-2">Default</span>
                                                @endif
                                            </h6>
                                            @if($selectedBillingId === $contact->id)
                                                <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                                            @endif
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-envelope mr-2"></i>{{ $contact->email }}
                                        </p>
                                        <p class="mb-2 text-muted small">
                                            <i class="fas fa-map-marker-alt mr-2"></i>
                                            {{ $contact->city }}, {{ $contact->country_code }}
                                        </p>
                                        <button wire:click="selectBilling({{ $contact->id }})" 
                                                class="btn btn-outline-success btn-sm w-100 {{ $selectedBillingId === $contact->id ? 'active' : '' }}">
                                            Select for Billing
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
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

<style>
.contact-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.contact-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.contact-card.selected .card {
    border: 2px solid #28a745;
    background-color: #f8fff9;
}

.contact-section {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}
</style>
