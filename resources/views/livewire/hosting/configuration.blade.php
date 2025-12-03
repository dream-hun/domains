<div>
    @section('page-title')
        Purchase Hosting - {{ $plan->name }}
    @endsection

    <style>
        /* Domain Option Cards */
        .domain-option-card {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
        }
        .domain-option-card:hover {
            box-shadow: 0 4px 12px rgba(77, 182, 172, 0.15);
            transform: translateY(-1px);
        }
        .domain-option-card.selected {
            box-shadow: 0 4px 15px rgba(77, 182, 172, 0.3);
        }

        /* Custom Radio Button Styling */
        .domain-radio {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #cbd5e0;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        .domain-radio-inner {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: transparent;
            transition: all 0.2s ease;
        }
        .domain-option-card.selected .domain-radio {
            border-color: rgba(255, 255, 255, 0.8);
            background-color: #fff;
        }
        .domain-option-card.selected .domain-radio-inner {
            background-color: #4db6ac;
        }

        /* Source Toggle Cards */
        .source-toggle-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
        }
        .source-toggle-card:hover {
            border-color: #4db6ac;
        }
        .source-toggle-card.selected {
            background: linear-gradient(135deg, #4db6ac 0%, #5bc0be 100%);
            border-color: #4db6ac;
            color: #fff;
        }
        .source-toggle-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Search Input */
        .search-input-group {
            display: flex;
            align-items: center;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            background-color: #fff;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        .search-input-group:focus-within {
            border-color: #4db6ac;
            box-shadow: 0 0 0 4px rgba(77, 182, 172, 0.1);
        }
        .search-input-group .search-icon-svg {
            width: 24px;
            height: 24px;
            color: #94a3b8;
            flex-shrink: 0;
            margin-right: 0.75rem;
        }
        .search-input-group input {
            border: none;
            outline: none;
            flex: 1;
            padding: 0.5rem 0;
            background: transparent;
        }
        .search-input-group input::placeholder {
            color: #a0aec0;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .hover-bg-light:hover {
            background-color: #f8f9fa !important;
        }

        /* Input group focus styling */
        .input-group:focus-within {
            border-color: #4db6ac !important;
            box-shadow: 0 0 0 4px rgba(77, 182, 172, 0.1) !important;
        }
    </style>

    <div class="rts-hosting-banner rts-hosting-banner-bg" style="min-height: 200px;">
        <div class="container">
            <div class="row">
                <div class="banner-area">
                    <div class="rts-hosting-banner rts-hosting-banner__content about__banner">
                        <h1 class="banner-title">Purchase Your Hosting</h1>
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
                    <div class="card-header text-center py-4" style="background: linear-gradient(135deg, #0556D1 0%, #0556D1 100%);">
                        <div class="mb-2">
                            <span>
                                <bi class="bi-hdd-rack text-white" style="font-size: 6rem;"></bi>
                            </span>
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
            <div class="col-lg-8 col-md-7 hosting-config">
                {{-- Step Header --}}
                <div class="mb-4">
                    <h2 class="widget-title">2. Domain Name Connection</h2>
                    <p class="brand-desc">Connect a domain to your Hosting Plan.</p>
                </div>
                <div class="mb-4">
                    <label class="form-label brand-desc mb-3">Connect to:</label>

                    {{-- New Domain Name Option --}}
                    <div class="domain-option-card mb-3 cursor-pointer {{ $domainOption === 'new' ? 'selected' : '' }}"
                         wire:click="$set('domainOption', 'new')"
                         style="
                            border-radius: 12px;
                            border: 2px solid {{ $domainOption === 'new' ? '#4db6ac' : '#e2e8f0' }};
                            background: {{ $domainOption === 'new' ? 'linear-gradient(135deg, #4db6ac 0%, #26a69a 100%)' : '#ffffff' }};
                            overflow: hidden;
                         ">
                        <div class="d-flex align-items-center px-4 py-3">
                            {{-- Custom Radio Circle --}}
                            <div class="domain-radio">
                                <div class="domain-radio-inner"></div>
                            </div>
                            <span class="ms-3 brand-desc" style="color: {{ $domainOption === 'new' ? '#ffffff' : '#2d3748' }};">
                                New Domain Name
                            </span>
                        </div>
                    </div>

                    {{-- Existing Domain Name Option --}}
                    <div class="domain-option-card cursor-pointer {{ $domainOption === 'existing' ? 'selected' : '' }}"
                         wire:click="$set('domainOption', 'existing')"
                         style="
                            border-radius: 12px;
                            border: 2px solid {{ $domainOption === 'existing' ? '#4db6ac' : '#e2e8f0' }};
                            background: {{ $domainOption === 'existing' ? 'linear-gradient(135deg, #4db6ac 0%, #26a69a 100%)' : '#ffffff' }};
                            overflow: hidden;
                         ">
                        <div class="d-flex align-items-center px-4 py-3">
                            {{-- Custom Radio Circle --}}
                            <div class="domain-radio">
                                <div class="domain-radio-inner"></div>
                            </div>
                            <span class="ms-3 brand-desc" style="color: {{ $domainOption === 'existing' ? '#ffffff' : '#2d3748' }};">
                                Existing Domain Name
                            </span>
                        </div>
                    </div>
                </div>

                {{-- NEW DOMAIN SECTION --}}
                @if($domainOption === 'new')
                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            {{-- Section Title with Divider --}}
                            <h5 class="fw-normal mb-3" style="color: #2d3748;">New Domain Name</h5>
                            <hr class="mt-0 mb-4" style="border-color: #e2e8f0;">

                            {{-- Already in Cart / New Purchase Toggle --}}
                            <div class="d-flex gap-3 mb-4">
                                {{-- Already in Cart Option --}}
                                <div class="source-toggle-card {{ $newDomainSource === 'already_in_cart' ? 'selected' : '' }} {{ $this->domainsInCart->isEmpty() ? 'disabled' : '' }}"
                                     style="flex: 1;"
                                     @if($this->domainsInCart->isNotEmpty()) wire:click="$set('newDomainSource', 'already_in_cart')" @endif>
                                    <div class="mb-2">
                                        <div class="domain-radio mx-auto" style="{{ $newDomainSource === 'already_in_cart' ? 'border-color: rgba(255,255,255,0.8);' : '' }}">
                                            <div class="domain-radio-inner" style="{{ $newDomainSource === 'already_in_cart' ? 'background-color: #4db6ac;' : '' }}"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- New Purchase Option --}}
                                <div class="source-toggle-card {{ $newDomainSource === 'new_purchase' ? 'selected' : '' }}"
                                     style="flex: 1;"
                                     wire:click="$set('newDomainSource', 'new_purchase')">
                                    <div class="mb-2">
                                        <div class="domain-radio mx-auto" style="{{ $newDomainSource === 'new_purchase' ? 'border-color: rgba(255,255,255,0.8);' : '' }}">
                                            <div class="domain-radio-inner" style="{{ $newDomainSource === 'new_purchase' ? 'background-color: #4db6ac;' : '' }}"></div>
                                        </div>
                                    </div>
                                    <div class="small" style="color: {{ $newDomainSource === 'new_purchase' ? '#fff' : '#718096' }};">New Purchase</div>
                                </div>
                            </div>

                            @if($newDomainSource === 'already_in_cart')
                                {{-- Domains Already in Cart --}}
                                @if($this->domainsInCart->isNotEmpty())
                                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                                        <div class="card-header bg-white border-0 pt-4 px-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-cart-check text-success me-2" style="font-size: 1.2rem;"></i>
                                                <h6 class="mb-0 fw-bold">Domains in Your Cart</h6>
                                            </div>
                                            <p class="text-muted small mb-0">Select a domain to connect to your hosting plan</p>
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($this->domainsInCart as $cartItem)
                                                <div class="d-flex justify-content-between align-items-center p-4 border-bottom cursor-pointer {{ $selectedDomain === $cartItem->name ? 'bg-light' : 'hover-bg-light' }}"
                                                     wire:click="selectCartDomain('{{ $cartItem->name }}')"
                                                     style="transition: all 0.2s ease; {{ $selectedDomain === $cartItem->name ? 'border-left: 4px solid #4db6ac !important;' : '' }}">
                                                    <div class="d-flex align-items-center">
                                                        <div class="domain-radio me-3" style="width: 20px; height: 20px; {{ $selectedDomain === $cartItem->name ? 'border-color: #4db6ac;' : '' }}">
                                                            <div class="domain-radio-inner" style="width: 10px; height: 10px; {{ $selectedDomain === $cartItem->name ? 'background-color: #4db6ac;' : '' }}"></div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold">{{ $cartItem->name }}</div>
                                                            <div class="text-muted small">Ready to connect</div>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge {{ $selectedDomain === $cartItem->name ? 'bg-primary' : 'bg-success' }} px-3 py-2">
                                                            <i class="bi bi-check-circle-fill me-1"></i>
                                                            In Cart
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if($selectedDomain && !$domainConfirmed)
                                        {{-- Domain Configuration Card --}}
                                        <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                                            <div class="card-body p-4">
                                                {{-- Domain Details --}}
                                                <div class="mb-4">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="rounded bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                            <i class="bi bi-globe" style="font-size: 1.2rem;"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold fs-5">{{ $selectedDomain }}</div>
                                                            <div class="text-muted small">Domain from your cart</div>
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-info border-0" style="background: rgba(77, 182, 172, 0.1); color: #2d5f5f; border-radius: 8px;">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        This domain will be connected to your hosting plan. No additional charges apply.
                                                    </div>
                                                </div>

                                                {{-- Connect Button --}}
                                                <div class="text-center">
                                                    <button class="btn btn-primary btn-lg px-5 py-3 fw-semibold" wire:click="confirmDomainSelection" wire:loading.attr="disabled" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(77, 182, 172, 0.3);">
                                                        <span wire:loading.remove wire:target="confirmDomainSelection">
                                                            <i class="bi bi-link-45deg me-2"></i>
                                                            Connect To Hosting
                                                        </span>
                                                        <span wire:loading wire:target="confirmDomainSelection">
                                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                                            Connecting...
                                                        </span>
                                                    </button>
                                                </div>
                                            </div>
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
                                <div class="mb-4">
                                    <p class="mb-2" style="color: #4a5568;">
                                        Simply search for your ideal domain name below.<br>
                                        Learn more about <a href="#" style="color: #4db6ac; text-decoration: none;">valid domain name entries</a>.
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold mb-2" style="color: #2d3748;">Find a domain name:</label>
                                    <div class="search-input-group @error('domainSearchQuery') border-danger @enderror">
                                        <svg wire:loading.remove wire:target="searchDomains" class="search-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <path d="m21 21-4.35-4.35"></path>
                                        </svg>

                                        <input type="text"
                                               wire:model="domainSearchQuery"
                                               wire:keydown.enter="searchDomains"
                                               wire:loading.attr="disabled"
                                               wire:target="searchDomains"
                                               placeholder="Search for domains" class="brand-desc">
                                        <div wire:loading wire:target="searchDomains" class="spinner-border text-primary" role="status" style="display: none; width: 24px; height: 24px; margin-right: 0.75rem;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    @error('domainSearchQuery')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Selected Domain Display (Confirmed) --}}
                                @if($domainConfirmed && $selectedDomain)
                                    <div class="alert alert-success d-flex align-items-center" style="background-color: #e8f5e9; border: none; border-radius: 12px;">
                                        <i class="bi bi-check-circle-fill text-success me-3" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <div class="fw-bold text-success fs-5">{{ $selectedDomain }}</div>
                                            <div class="text-success">Connected! Click "Add to Cart" below to proceed.</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Search Results --}}
                                @if(!empty($domainSearchResults))
                                    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                                        <div class="card-header bg-white border-0 pt-4 px-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-search text-primary me-2" style="font-size: 1.2rem;"></i>
                                                <h6 class="mb-0 fw-bold">Search Results</h6>
                                            </div>
                                            <p class="text-muted small mb-0">Click on a domain to select it</p>
                                        </div>
                                        <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                                            @foreach($domainSearchResults as $domain => $result)
                                                <div class="d-flex justify-content-between align-items-center p-4 border-bottom cursor-pointer hover-bg-light {{ $selectedDomain === $domain ? 'bg-light border-primary' : '' }} {{ !$result['available'] ? 'opacity-50' : '' }}"
                                                     @if($result['available']) wire:click="selectDomain('{{ $domain }}', {{ $result['price'] }})" @endif
                                                     style="transition: all 0.2s ease; {{ $selectedDomain === $domain ? 'border-left: 4px solid #4db6ac !important;' : '' }}">
                                                    <div class="d-flex align-items-center">
                                                        <div class="domain-radio me-3" style="width: 20px; height: 20px; {{ $selectedDomain === $domain ? 'border-color: #4db6ac;' : '' }}">
                                                            <div class="domain-radio-inner" style="width: 10px; height: 10px; {{ $selectedDomain === $domain ? 'background-color: #4db6ac;' : '' }}"></div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold">{{ $domain }}</div>
                                                            <div class="text-muted small">{{ $result['available'] ? 'Available for registration' : 'Not available' }}</div>
                                                        </div>
                                                        @if(!$result['available'])
                                                            <span class="badge bg-secondary ms-2">TAKEN</span>
                                                        @endif
                                                    </div>
                                                    @if($result['available'])
                                                        <div class="text-end">
                                                            <div class="fw-bold text-success">{{ $result['formatted_price'] }} /yr</div>
                                                            <div class="small text-muted">Renews {{ $result['formatted_renewal'] }} /yr</div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Selected Domain Summary with Connect Button --}}
                                    @if($selectedDomain && isset($domainSearchResults[$selectedDomain]) && $domainSearchResults[$selectedDomain]['available'] && !$domainConfirmed)
                                        {{-- Domain Configuration Card --}}
                                        <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                                            <div class="card-body p-4">
                                                {{-- Domain Details --}}
                                                <div class="mb-4">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="rounded bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                            <i class="bi bi-globe" style="font-size: 1.2rem;"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold fs-5">{{ $selectedDomain }}</div>
                                                            <div class="text-muted small">New domain purchase</div>
                                                        </div>
                                                    </div>
                                                    <div class="alert alert-info border-0" style="background: rgba(77, 182, 172, 0.1); color: #2d5f5f; border-radius: 8px;">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        This domain will be purchased and connected to your hosting plan.
                                                        <div class="mt-2">
                                                            <strong>{{ $domainSearchResults[$selectedDomain]['formatted_price'] }} /yr</strong>
                                                            <span class="ms-2">Renews {{ $domainSearchResults[$selectedDomain]['formatted_renewal'] }} /yr</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Connect Button --}}
                                                <div class="text-center">
                                                    <button class="btn btn-primary btn-lg px-5 py-3 fw-semibold" wire:click="confirmDomainSelection" wire:loading.attr="disabled" style="border-radius: 12px; box-shadow: 0 4px 12px rgba(77, 182, 172, 0.3);">
                                                        <span wire:loading.remove wire:target="confirmDomainSelection">
                                                            <i class="bi bi-link-45deg me-2"></i>
                                                            Connect To Hosting
                                                        </span>
                                                        <span wire:loading wire:target="confirmDomainSelection">
                                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                                            Connecting...
                                                        </span>
                                                    </button>
                                                </div>
                                            </div>
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
                                    <span class="ms-2"> is connected.</span>
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
                                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center cursor-pointer {{ $selectedOwnedDomainId === $domain->id ? 'active border-primary' : '' }}"
                                                     wire:click="selectOwnedDomain({{ $domain->id }})">
                                                    <div class="d-flex align-items-center">
                                                        <div class="domain-radio me-3" style="width: 20px; height: 20px; {{ $selectedOwnedDomainId === $domain->id ? 'border-color: #fff;' : '' }}">
                                                            <div class="domain-radio-inner" style="width: 10px; height: 10px; {{ $selectedOwnedDomainId === $domain->id ? 'background-color: #4db6ac;' : '' }}"></div>
                                                        </div>
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
                                        <label class="form-label fw-semibold mb-2" style="color: #2d3748;">Enter your external domain name:</label>
                                        <div class="search-input-group @error('externalDomainName') border-danger @enderror">
                                            <svg wire:loading.remove wire:target="validateExternalDomain" class="search-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                            </svg>
                                            <div wire:loading wire:target="validateExternalDomain" class="spinner-border text-primary" role="status" style="display: none; width: 24px; height: 24px; margin-right: 0.75rem;">
                                                <span class="visually-hidden">Validating...</span>
                                            </div>
                                            <input type="text"
                                                   wire:model.blur="externalDomainName"
                                                   wire:keydown.enter="validateExternalDomain"
                                                   wire:loading.attr="disabled"
                                                   wire:target="validateExternalDomain"
                                                   placeholder="example.com" class="brand-desc">
                                        </div>
                                        <div class="form-text mt-2">
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
                                        <label class="form-label fw-semibold mb-2" style="color: #2d3748;">Or enter an external domain:</label>
                                        <div class="search-input-group @error('externalDomainName') border-danger @enderror">
                                            <svg wire:loading.remove wire:target="validateExternalDomain" class="search-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                            </svg>
                                            <div wire:loading wire:target="validateExternalDomain" class="spinner-border text-primary" role="status" style="display: none; width: 24px; height: 24px; margin-right: 0.75rem;">
                                                <span class="visually-hidden">Validating...</span>
                                            </div>
                                            <input type="text"
                                                   wire:model.blur="externalDomainName"
                                                   wire:keydown.enter="validateExternalDomain"
                                                   wire:loading.attr="disabled"
                                                   wire:target="validateExternalDomain"
                                                   placeholder="example.com" class="brand-desc">
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
    <div class="cart-summary-container fixed-bottom bg-white shadow-lg p-3 border-top" style="z-index: 999; font-size: 16px; line-height: 26px;">
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
                    <div class="d-flex justify-content-end align-items-center gap-3">
                        <a href="{{ route('hosting.categories.show', $plan->category->slug) }}" class="btn btn-outline-danger btn-lg px-5 py-3 fw-semibold text-center" style="border-radius: 12px; min-width: 120px;">Cancel</a>
                        <button class="btn {{ $this->canAddToCart ? 'btn-success' : 'btn-secondary' }} btn-lg px-5 py-3 fw-semibold text-center"
                                wire:click="addToCart"
                                wire:loading.attr="disabled"
                                @if(!$this->canAddToCart) disabled @endif
                                style="border-radius: 12px; box-shadow: {{ $this->canAddToCart ? '0 4px 12px rgba(76, 175, 80, 0.3)' : 'none' }}; min-width: 160px;">
                            <span wire:loading.remove wire:target="addToCart">
                                <i class="bi bi-cart-plus me-2"></i>
                                Add to Cart
                            </span>
                            <span wire:loading wire:target="addToCart">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Adding...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
