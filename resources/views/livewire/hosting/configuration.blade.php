<div>
    @section('page-title')
        Configure Hosting - {{ $plan->name }}
    @endsection

    <div class="rts-hosting-banner rts-hosting-banner-bg" style="min-height: 200px;">
        <div class="container">
            <div class="row">
                <div class="banner-area">
                    <div class="rts-hosting-banner rts-hosting-banner__content about__banner">
                        <h1 class="banner-title">Configure Your Hosting</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5" style="padding-bottom: 120px;">
        <div class="row">
            {{-- Left Column: Plan Summary Card --}}
            <div class="col-lg-4 col-md-5 mb-4">
                <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; position: sticky; top: 20px;">
                    {{-- Plan Header --}}
                    <div class="card-header text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="mb-2">
                            <div class="d-flex justify-content-center gap-2 mb-2">
                                <span class="bg-white rounded" style="width: 30px; height: 12px;"></span>
                                <span class="bg-white rounded" style="width: 12px; height: 12px;"></span>
                                <span class="bg-white rounded" style="width: 12px; height: 12px;"></span>
                            </div>
                            <div class="d-flex justify-content-center gap-1">
                                <span class="bg-white rounded-circle" style="width: 8px; height: 8px;"></span>
                                <span class="bg-white rounded-circle" style="width: 8px; height: 8px;"></span>
                                <span class="bg-white rounded-circle" style="width: 8px; height: 8px;"></span>
                                <span class="bg-white rounded-circle" style="width: 8px; height: 8px;"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Plan Details --}}
                    <div class="card-body p-4">
                        <h4 class="mb-4 fw-bold">{{ $plan->name }}</h4>

                        {{-- Key Features --}}
                        @foreach($this->highlightedFeatures as $feature)
                            <div class="mb-3">
                                <div class="text-muted small fw-semibold">{{ $feature['name'] }}</div>
                                <div class="fw-bold">{{ $feature['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right Column: Domain Connection --}}
            <div class="col-lg-8 col-md-7">
                {{-- Step Header --}}
                <div class="mb-4">
                    <h2 class="fw-bold mb-2">2. Domain Name Connection</h2>
                    <p class="text-muted">Connect a domain to your Hosting Plan.</p>
                </div>

                {{-- Connect to Options --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Connect to:</label>
                    
                    {{-- New Domain Name Option --}}
                    <div class="card mb-2 cursor-pointer {{ $domainOption === 'new' ? 'border-2' : 'border' }}" 
                         style="border-radius: 8px; {{ $domainOption === 'new' ? 'border-color: #5bc0be !important; background-color: #5bc0be; color: white;' : '' }}"
                         wire:click="$set('domainOption', 'new')">
                        <div class="card-body py-3 d-flex align-items-center">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="domainOption" id="option_new" 
                                       value="new" wire:model.live="domainOption" style="margin-top: 0;">
                            </div>
                            <label class="form-check-label ms-2 cursor-pointer fw-semibold" for="option_new">
                                New Domain Name
                            </label>
                        </div>
                    </div>

                    {{-- Existing Domain Name Option --}}
                    <div class="card cursor-pointer {{ $domainOption === 'existing' ? 'border-2' : 'border' }}" 
                         style="border-radius: 8px; {{ $domainOption === 'existing' ? 'border-color: #5bc0be !important; background-color: #5bc0be; color: white;' : '' }}"
                         wire:click="$set('domainOption', 'existing')">
                        <div class="card-body py-3 d-flex align-items-center">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="domainOption" id="option_existing" 
                                       value="existing" wire:model.live="domainOption" style="margin-top: 0;">
                            </div>
                            <label class="form-check-label ms-2 cursor-pointer fw-semibold" for="option_existing">
                                Existing Domain Name
                            </label>
                        </div>
                    </div>
                </div>

                {{-- NEW DOMAIN SECTION --}}
                @if($domainOption === 'new')
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">New Domain Name</h5>

                            {{-- Already in Cart / New Purchase Toggle --}}
                            <div class="d-flex gap-3 mb-4">
                                <div class="card flex-fill {{ $this->domainsInCart->isNotEmpty() ? 'cursor-pointer' : '' }} {{ $newDomainSource === 'already_in_cart' ? 'border-primary' : 'border' }}" 
                                     style="border-radius: 8px; opacity: {{ $this->domainsInCart->isEmpty() ? '0.5' : '1' }};"
                                     @if($this->domainsInCart->isNotEmpty()) wire:click="$set('newDomainSource', 'already_in_cart')" @endif>
                                    <div class="card-body text-center py-3">
                                        <div class="mb-2">
                                            <i class="bi bi-cart {{ $newDomainSource === 'already_in_cart' ? 'text-primary' : 'text-muted' }}" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div class="fw-semibold small">Already in Cart</div>
                                    </div>
                                </div>
                                <div class="card flex-fill cursor-pointer {{ $newDomainSource === 'new_purchase' ? 'border-primary bg-light' : 'border' }}" 
                                     style="border-radius: 8px; {{ $newDomainSource === 'new_purchase' ? 'background-color: #5bc0be !important;' : '' }}"
                                     wire:click="$set('newDomainSource', 'new_purchase')">
                                    <div class="card-body text-center py-3 {{ $newDomainSource === 'new_purchase' ? 'text-white' : '' }}">
                                        <div class="mb-2">
                                            <i class="bi bi-plus-circle" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div class="fw-semibold small">New Purchase</div>
                                    </div>
                                </div>
                            </div>

                            @if($newDomainSource === 'already_in_cart')
                                {{-- Domains Already in Cart --}}
                                @if($this->domainsInCart->isNotEmpty())
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Select a domain from your cart:</label>
                                        <div class="list-group">
                                            @foreach($this->domainsInCart as $cartItem)
                                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center cursor-pointer {{ $selectedDomain === $cartItem->name ? 'active' : '' }}"
                                                     wire:click="selectCartDomain('{{ $cartItem->name }}')">
                                                    <div class="d-flex align-items-center">
                                                        <input type="radio" class="form-check-input me-3" 
                                                               {{ $selectedDomain === $cartItem->name ? 'checked' : '' }}>
                                                        <span class="fw-semibold">{{ $cartItem->name }}</span>
                                                    </div>
                                                    <span class="badge bg-success">In Cart</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if($selectedDomain && !$domainConfirmed)
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-primary px-4" wire:click="confirmDomainSelection" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="confirmDomainSelection">Connect To Hosting</span>
                                                <span wire:loading wire:target="confirmDomainSelection">
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                </span>
                                            </button>
                                        </div>
                                    @endif

                                    @if($domainConfirmed && $selectedDomain)
                                        <div class="alert alert-success d-flex align-items-center">
                                            <i class="bi bi-check-circle-fill me-2"></i>
                                            <strong>{{ $selectedDomain }}</strong> is connected. Click "Add to Cart" below to proceed.
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        No domains in your cart. Please search for a new domain.
                                    </div>
                                @endif
                            @else
                                {{-- New Domain Search --}}
                                <div class="mb-3">
                                    <p class="text-muted mb-3">Simply search for your ideal domain name below.</p>
                                    
                                    <label class="form-label fw-semibold">Find a domain name:</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                        <input type="text" 
                                               class="form-control form-control-lg @error('domainSearchQuery') is-invalid @enderror" 
                                               wire:model="domainSearchQuery"
                                               wire:keydown.enter="searchDomains"
                                               placeholder="Search for domains">
                                        <button class="btn btn-primary px-4" wire:click="searchDomains" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="searchDomains">Search</span>
                                            <span wire:loading wire:target="searchDomains">
                                                <span class="spinner-border spinner-border-sm"></span>
                                            </span>
                                        </button>
                                    </div>
                                    @error('domainSearchQuery')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Selected Domain Display (Confirmed) --}}
                                @if($domainConfirmed && $selectedDomain)
                                    <div class="alert alert-success d-flex align-items-center mb-3" style="background-color: #e8f5e9; border: none;">
                                        <i class="bi bi-check-lg text-success me-2" style="font-size: 1.5rem;"></i>
                                        <span class="fw-bold text-success" style="font-size: 1.25rem;">{{ $selectedDomain }}</span>
                                        <span class="ms-auto text-success">Connected! Click "Add to Cart" below.</span>
                                    </div>
                                @endif

                                {{-- Search Results --}}
                                @if(!empty($domainSearchResults))
                                    <div class="card border" style="border-radius: 8px;">
                                        <div class="card-header bg-white py-2">
                                            <span class="fw-semibold text-muted small">Search Results</span>
                                            <i class="bi bi-info-circle text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </div>
                                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                                            @foreach($domainSearchResults as $domain => $result)
                                                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom cursor-pointer {{ $selectedDomain === $domain ? 'bg-light' : '' }} {{ !$result['available'] ? 'opacity-50' : '' }}"
                                                     @if($result['available']) wire:click="selectDomain('{{ $domain }}', {{ $result['price'] }})" @endif>
                                                    <div class="d-flex align-items-center">
                                                        <input type="radio" class="form-check-input me-3" 
                                                               {{ $selectedDomain === $domain ? 'checked' : '' }}
                                                               {{ !$result['available'] ? 'disabled' : '' }}>
                                                        <span class="fw-semibold">{{ $domain }}</span>
                                                        @if(!$result['available'])
                                                            <span class="badge bg-secondary ms-2">TAKEN</span>
                                                        @endif
                                                    </div>
                                                    @if($result['available'])
                                                        <div class="text-end">
                                                            <div class="fw-bold">{{ $result['formatted_price'] }} /yr</div>
                                                            <div class="small text-muted">Renews {{ $result['formatted_renewal'] }} /yr</div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Selected Domain Summary with Connect Button --}}
                                    @if($selectedDomain && isset($domainSearchResults[$selectedDomain]) && $domainSearchResults[$selectedDomain]['available'] && !$domainConfirmed)
                                        <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-light" style="border-radius: 8px;">
                                            <div>
                                                <div class="fw-bold" style="font-size: 1.1rem;">{{ $selectedDomain }}</div>
                                                <div class="text-muted small">
                                                    {{ $domainSearchResults[$selectedDomain]['formatted_price'] }} /yr
                                                    <span class="ms-2">Renews {{ $domainSearchResults[$selectedDomain]['formatted_renewal'] }} /yr</span>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary px-4" wire:click="confirmDomainSelection" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="confirmDomainSelection">Connect To Hosting</span>
                                                <span wire:loading wire:target="confirmDomainSelection">
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                </span>
                                            </button>
                                        </div>
                                    @endif
                                @endif

                                {{-- Loading State --}}
                                @if($isSearching)
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Searching...</span>
                                        </div>
                                        <p class="text-muted mt-2">Checking domain availability...</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif

                {{-- EXISTING DOMAIN SECTION --}}
                @if($domainOption === 'existing')
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">Existing Domain Name</h5>

                            @if(!$plan->category->allowsExternalDomain())
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>Note:</strong> {{ $plan->category->name }} plans require a domain registered with us. 
                                    You can only select from your owned domains or transfer a domain to us.
                                </div>
                            @endif

                            {{-- Confirmed Domain Display --}}
                            @if($domainConfirmed && ($selectedDomain || $externalDomainName))
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <strong>{{ $selectedDomain ?: $externalDomainName }}</strong>
                                    <span class="ms-2">is connected. Click "Add to Cart" below to proceed.</span>
                                </div>
                            @endif

                            @auth
                                {{-- Source Toggle --}}
                                <div class="d-flex gap-3 mb-4">
                                    <div class="card flex-fill cursor-pointer {{ $existingDomainSource === 'owned' ? 'border-primary bg-light' : 'border' }}" 
                                         style="border-radius: 8px;"
                                         wire:click="$set('existingDomainSource', 'owned')">
                                        <div class="card-body text-center py-3">
                                            <div class="mb-2">
                                                <i class="bi bi-collection {{ $existingDomainSource === 'owned' ? 'text-primary' : 'text-muted' }}" style="font-size: 1.5rem;"></i>
                                            </div>
                                            <div class="fw-semibold small">My Domains</div>
                                            <div class="text-muted small">{{ $this->userOwnedDomains->count() }} domains</div>
                                        </div>
                                    </div>
                                    @if($plan->category->allowsExternalDomain())
                                        <div class="card flex-fill cursor-pointer {{ $existingDomainSource === 'external' ? 'border-primary bg-light' : 'border' }}" 
                                             style="border-radius: 8px;"
                                             wire:click="$set('existingDomainSource', 'external')">
                                            <div class="card-body text-center py-3">
                                                <div class="mb-2">
                                                    <i class="bi bi-globe {{ $existingDomainSource === 'external' ? 'text-primary' : 'text-muted' }}" style="font-size: 1.5rem;"></i>
                                                </div>
                                                <div class="fw-semibold small">External Domain</div>
                                                <div class="text-muted small">I own it elsewhere</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if($existingDomainSource === 'owned')
                                    {{-- User's Owned Domains --}}
                                    @if($this->userOwnedDomains->isNotEmpty())
                                        <label class="form-label fw-semibold">Select one of your domains:</label>
                                        <div class="list-group mb-3" style="max-height: 300px; overflow-y: auto;">
                                            @foreach($this->userOwnedDomains as $domain)
                                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center cursor-pointer {{ $selectedOwnedDomainId === $domain->id ? 'active' : '' }}"
                                                     wire:click="selectOwnedDomain({{ $domain->id }})">
                                                    <div class="d-flex align-items-center">
                                                        <input type="radio" class="form-check-input me-3" 
                                                               {{ $selectedOwnedDomainId === $domain->id ? 'checked' : '' }}>
                                                        <div>
                                                            <span class="fw-semibold">{{ $domain->name }}</span>
                                                            @if($domain->expires_at)
                                                                <div class="small {{ $selectedOwnedDomainId === $domain->id ? 'text-white-50' : 'text-muted' }}">
                                                                    Expires: {{ $domain->expires_at->format('M d, Y') }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <span class="badge {{ $domain->status->color() }}">
                                                        {{ $domain->status->label() }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($selectedDomain && !$domainConfirmed)
                                            <div class="d-flex justify-content-between align-items-center p-3 bg-light" style="border-radius: 8px;">
                                                <div>
                                                    <div class="fw-bold">{{ $selectedDomain }}</div>
                                                    <div class="text-muted small">Will be connected to this hosting plan</div>
                                                </div>
                                                <button class="btn btn-primary px-4" wire:click="confirmDomainSelection" wire:loading.attr="disabled">
                                                    <span wire:loading.remove wire:target="confirmDomainSelection">Connect To Hosting</span>
                                                    <span wire:loading wire:target="confirmDomainSelection">
                                                        <span class="spinner-border spinner-border-sm"></span>
                                                    </span>
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            You don't have any domains registered with us yet.
                                            @if($plan->category->allowsExternalDomain())
                                                You can use an external domain instead.
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    {{-- External Domain Input --}}
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Enter your domain name:</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-globe2"></i></span>
                                            <input type="text" 
                                                   class="form-control form-control-lg @error('externalDomainName') is-invalid @enderror" 
                                                   wire:model.blur="externalDomainName"
                                                   placeholder="example.com">
                                        </div>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            You will need to update your nameservers to point to our hosting after purchase.
                                        </div>
                                        @error('externalDomainName')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    @if($externalDomainName && !$domainConfirmed)
                                        <div class="d-flex justify-content-between align-items-center p-3 bg-light" style="border-radius: 8px;">
                                            <div>
                                                <div class="fw-bold">{{ $externalDomainName }}</div>
                                                <div class="text-muted small">External domain - update nameservers after purchase</div>
                                            </div>
                                            <button class="btn btn-primary px-4" wire:click="confirmDomainSelection" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="confirmDomainSelection">Connect To Hosting</span>
                                                <span wire:loading wire:target="confirmDomainSelection">
                                                    <span class="spinner-border spinner-border-sm"></span>
                                                </span>
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            @else
                                {{-- Not logged in --}}
                                <div class="alert alert-warning">
                                    <i class="bi bi-person-exclamation me-2"></i>
                                    Please <a href="{{ route('login') }}" class="alert-link">sign in</a> to select from your existing domains.
                                </div>

                                @if($plan->category->allowsExternalDomain())
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Or enter an external domain:</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-globe2"></i></span>
                                            <input type="text" 
                                                   class="form-control form-control-lg" 
                                                   wire:model.blur="externalDomainName"
                                                   placeholder="example.com">
                                        </div>
                                    </div>

                                    @if($externalDomainName && !$domainConfirmed)
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-primary px-4" wire:click="confirmDomainSelection">
                                                Connect To Hosting
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            @endauth
                        </div>
                    </div>
                @endif

                @error('base')
                    <div class="alert alert-danger mt-3">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Bottom Summary Bar --}}
    <div class="fixed-bottom bg-white border-top shadow-lg py-3" style="z-index: 1000;">
        <div class="container">
            <div class="row align-items-center">
                {{-- Step 1 Summary --}}
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-hdd-rack text-muted"></i>
                        </div>
                        <div>
                            <div class="small text-muted">1. Hosting Customization <a href="{{ route('hosting.categories.show', $plan->category->slug) }}" class="text-danger">EDIT</a></div>
                            <div class="fw-semibold">{{ $plan->category->name }} "{{ $plan->name }}"</div>
                            <div class="text-primary fw-bold">{{ $this->formattedPrice }}{{ $this->billingPeriodLabel }}</div>
                        </div>
                    </div>
                </div>

                {{-- Step 2 Summary --}}
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded {{ $domainConfirmed ? 'bg-success text-white' : ($this->finalDomainName ? 'bg-warning' : 'bg-light') }} d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            @if($domainConfirmed)
                                <i class="bi bi-check-lg"></i>
                            @else
                                <i class="bi bi-globe"></i>
                            @endif
                        </div>
                        <div>
                            <div class="small text-muted">2. Domain Name Connection</div>
                            @if($domainConfirmed && $this->finalDomainName)
                                <div class="fw-semibold text-success">{{ $this->finalDomainName }}</div>
                            @elseif($this->finalDomainName)
                                <div class="fw-semibold text-warning">{{ $this->finalDomainName }} <small>(not confirmed)</small></div>
                            @else
                                <div class="text-muted">Connect a domain name to your Hosting Plan</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Total --}}
                <div class="col-md-2 text-center">
                    <div class="small text-muted">Total</div>
                    <div class="h4 mb-0 fw-bold">{{ $this->formattedTotalPrice }}</div>
                </div>

                {{-- Actions --}}
                <div class="col-md-3 text-end">
                    <a href="{{ route('hosting.categories.show', $plan->category->slug) }}" class="btn btn-link text-danger me-2">Cancel</a>
                    <button class="btn px-4" 
                            wire:click="addToCart" 
                            wire:loading.attr="disabled"
                            @if(!$this->canAddToCart) disabled @endif
                            style="{{ $this->canAddToCart ? 'background-color: #e57373; border-color: #e57373; color: white;' : 'background-color: #ccc; border-color: #ccc; color: #666;' }}">
                        <span wire:loading.remove wire:target="addToCart">Add to Cart</span>
                        <span wire:loading wire:target="addToCart">
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
