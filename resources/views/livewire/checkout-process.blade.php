<div>
    @if (empty($cartItems))
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shopping-cart mr-2"></i>Empty Cart
                        </h3>
                    </div>
                    <div class="card-body text-center py-5">
                        <div class="text-muted mb-4">
                            <i class="fas fa-cart-plus fa-5x"></i>
                        </div>
                        <h4 class="text-muted">Your cart is empty</h4>
                        <p class="text-muted">Add some domains to your cart before proceeding to checkout.</p>
                        <a href="{{ route('domains') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-search mr-2"></i>Search Domains
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <!-- Order Review Section -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-check mr-2"></i>Order Review
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Domain</th>
                                        <th>Type</th>
                                        <th>Years</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cartItems as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item['name'] }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">
                                                    {{ ucfirst($item['attributes']['type'] ?? 'registration') }}
                                                </span>
                                            </td>
                                            <td>{{ $item['quantity'] }} {{ Str::plural('Year', $item['quantity']) }}</td>
                                            <td>
                                                @if (isset($item['attributes']['currency']) && $item['attributes']['currency'] !== 'USD')
                                                    {{ number_format($item['price'], 2) }} {{ $item['attributes']['currency'] }}
                                                @else
                                                    ${{ number_format($item['price'], 2) }}
                                                @endif
                                            </td>
                                            <td>
                                                <strong>
                                                    @if (isset($item['attributes']['currency']) && $item['attributes']['currency'] !== 'USD')
                                                        {{ number_format($item['price'] * $item['quantity'], 2) }} {{ $item['attributes']['currency'] }}
                                                    @else
                                                        ${{ number_format($item['price'] * $item['quantity'], 2) }}
                                                    @endif
                                                </strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Selection Section -->
            <div class="col-lg-8 mt-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user mr-2"></i>Contact Information
                        </h3>
                    </div>
                    <div class="card-body">
                        @if (empty($userContacts))
                            <div class="alert alert-warning">
                                <h5 class="alert-heading">No Contacts Found</h5>
                                <p>You need to create a contact profile before registering domains.</p>
                                <a href="{{ route('admin.contacts.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i>Create Contact
                                </a>
                            </div>
                        @else
                            <p class="text-muted mb-3">Select the contact information to use for domain registration:</p>
                            
                            @if ($selectedContactId)
                                @php
                                    $selectedContact = collect($userContacts)->firstWhere('id', $selectedContactId);
                                @endphp
                                @if ($selectedContact)
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-check mr-1"></i>Selected Contact</span>
                                            <button type="button" class="btn btn-sm btn-outline-light" wire:click="toggleContactSelection">
                                                Change
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $selectedContact['first_name'] }} {{ $selectedContact['last_name'] }}</h6>
                                            <p class="card-text small mb-0">
                                                <strong>Email:</strong> {{ $selectedContact['email'] }}<br>
                                                <strong>Phone:</strong> {{ $selectedContact['phone'] }}<br>
                                                <strong>Address:</strong> {{ $selectedContact['address_one'] }}, {{ $selectedContact['city'] }}
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if ($showContactSelection || !$selectedContactId)
                                <div class="row">
                                    @foreach ($userContacts as $contact)
                                        <div class="col-md-6 mb-3">
                                            <div class="card border {{ $selectedContactId == $contact['id'] ? 'border-primary' : '' }} contact-card" 
                                                 style="cursor: pointer;" wire:click="selectContact({{ $contact['id'] }})">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="card-title">
                                                                {{ $contact['first_name'] }} {{ $contact['last_name'] }}
                                                                @if ($contact['is_primary'])
                                                                    <span class="badge badge-primary badge-sm">Primary</span>
                                                                @endif
                                                            </h6>
                                                            <p class="card-text small">
                                                                <strong>Email:</strong> {{ $contact['email'] }}<br>
                                                                <strong>Phone:</strong> {{ $contact['phone'] }}<br>
                                                                <strong>Address:</strong> {{ $contact['address_one'] }}, {{ $contact['city'] }}
                                                            </p>
                                                        </div>
                                                        @if ($selectedContactId == $contact['id'])
                                                            <i class="fas fa-check-circle text-primary"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="text-center mt-3">
                                    <a href="{{ route('admin.contacts.create') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-plus mr-1"></i>Create New Contact
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <!-- Order Summary & Payment Section -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-receipt mr-2"></i>Order Summary
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Messages -->
                        @if ($errorMessage)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ $errorMessage }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if ($successMessage)
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ $successMessage }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <!-- Coupon Section -->
                        <div class="mb-4">
                            <label for="couponCode" class="form-label">
                                <i class="fas fa-ticket-alt mr-1"></i>Coupon Code
                            </label>
                            @if ($appliedCoupon)
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ $appliedCoupon->code }}" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-danger" type="button" wire:click="removeCoupon">
                                            <i class="fas fa-times mr-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="input-group">
                                    <input type="text" class="form-control" wire:model="couponCode" 
                                           placeholder="Enter coupon code" wire:keydown.enter="applyCoupon">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary" type="button" 
                                                wire:click="applyCoupon" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="applyCoupon">
                                                <i class="fas fa-check mr-1"></i>Apply
                                            </span>
                                            <span wire:loading wire:target="applyCoupon">
                                                <i class="fas fa-spinner fa-spin mr-1"></i>Applying...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Order Totals -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>${{ number_format($subtotal, 2) }}</span>
                            </div>

                            @if ($discount > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-success">
                                        Discount @if($appliedCoupon)({{ $appliedCoupon->code }})@endif:
                                    </span>
                                    <span class="text-success">-${{ number_format($discount, 2) }}</span>
                                </div>
                            @endif

                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>${{ number_format($total, 2) }}</strong>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="mt-4">
                            <h5 class="mb-3">
                                <i class="fas fa-credit-card mr-2"></i>Payment Method
                            </h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" wire:model="paymentMethod" 
                                       id="paymentStripe" value="stripe">
                                <label class="form-check-label" for="paymentStripe">
                                    <i class="fas fa-credit-card mr-1"></i>Credit Card (Stripe)
                                </label>
                            </div>
                        </div>

                        <!-- Proceed to Payment Button -->
                        <div class="mt-4">
                            @if (!$selectedContactId && !empty($userContacts))
                                <div class="alert alert-warning mb-3">
                                    <small><i class="fas fa-exclamation-triangle mr-1"></i>Please select a contact before proceeding to payment.</small>
                                </div>
                            @endif
                            
                            <button type="button" 
                                    class="btn btn-success btn-lg btn-block {{ (!$selectedContactId && !empty($userContacts)) ? 'disabled' : '' }}" 
                                    wire:click="proceedToPayment" 
                                    wire:loading.attr="disabled"
                                    {{ (!$selectedContactId && !empty($userContacts)) ? 'disabled' : '' }}>
                                <span wire:loading.remove wire:target="proceedToPayment">
                                    <i class="fas fa-credit-card mr-2"></i>Proceed to Payment
                                    <span class="float-right">${{ number_format($total, 2) }}</span>
                                </span>
                                <span wire:loading wire:target="proceedToPayment">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Processing...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <style>
    .contact-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
        transition: all 0.2s ease;
    }

    .contact-card.border-primary {
        box-shadow: 0 4px 12px rgba(0,123,255,0.2);
    }
    </style>
</div>