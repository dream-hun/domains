<div x-data="{ loading: false }" @loading.window="loading = $event.detail.loading">
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(10, 10, 30, 0.5);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: all 0.4s ease;
            width: 100vw;
            height: 100vh;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
            transform: translateY(0);
            transition: transform 0.4s ease;
            width: 90%;
            max-width: 600px;
            color: white;
        }

        .spinner {
            width: 4rem;
            height: 4rem;
            border: 4px solid rgba(0, 123, 255, 0.1);
            border-radius: 50%;
            border-top: 4px solid #007bff;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .btn-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 2px solid #ffffff;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Custom height for hero section */
        .rts-hero-three {
            max-height: 550px !important;
            height: 550px !important;
            min-height: 550px !important;
            padding-top: 250px !important;
            padding-bottom: 70px !important;
            overflow: hidden;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .results-wrapper {
            position: relative;
            min-height: 200px;
        }

        .domain-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }

        .domain-badge.available {
            background-color: #28a745;
            color: white;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }

        .domain-badge.unavailable {
            background-color: #dc3545;
            color: white;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        .domain-badge.bg-info {
            background-color: #17a2b8;
            color: white;
            box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
        }

        .cart-button {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 75%;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .cart-button.add {
            color: white;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
        }

        .cart-button.add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(40, 167, 69, 0.3);
        }

        .cart-button.remove {
            color: white;
            box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);
        }

        .cart-button.remove:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(220, 53, 69, 0.3);
        }

        .cart-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
    </style>

    <section class="rts-hero-three rts-hero__one rts-hosting-banner domain-checker-padding"
             style="max-height: 300px; height: 300px; min-height: 300px; padding-top: 30px; padding-bottom: 30px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <div class="rts-hero__content domain">
                        <h1 data-sal="slide-down" data-sal-delay="100" data-sal-duration="800" class="sal-animate"
                            style="font-size: 2.5rem !important; margin-bottom: 25px;">
                            Search for a Domain you desire
                        </h1>

                        <form wire:submit.prevent="search" id="domainForm" data-sal-delay="300" data-sal-duration="800"
                              x-on:submit="$dispatch('loading', { loading: true })">
                            <!-- Added Alpine.js event dispatch -->
                            <div class="rts-hero__form-area">
                                <input type="text" placeholder="Type your domain without extension Ex: jhonsmith"
                                       wire:model.defer="domain" id="domainText" autocomplete="off"
                                       class="form-select @error('domain') is-invalid @enderror">
                                <div class="select-button-area">
                                    <select wire:model.defer="extension" id="domainExtension">
                                        @foreach ($tlds as $tld)
                                            <option value="{{ $tld->tld }}">{{ $tld->tld }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" id="checkButton" wire:loading.attr="disabled"
                                            wire:target="search" x-on:click="$dispatch('loading', { loading: true })">
                                        <span wire:loading.remove wire:target="search">Search</span>
                                        <span wire:loading wire:target="search">Searching...</span>
                                    </button>
                                </div>
                            </div>
                            @error('domain')
                            <div class="domain-search-error">{{ $message }}</div>
                            @enderror
                        </form>
                        <div class="banner-content-tag" data-sal-delay="400" data-sal-duration="800">
                            <p class="desc">Popular Domain:</p>
                            <ul class="tag-list">
                                @foreach ($tlds as $tld)
                                    <li>
                                        <span>{{ $tld->tld }}</span>
                                        <span>{{ $tld->getFormattedPriceWithFallback('register_price', $this->currentCurrency) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="banner-shape-area">
            <img class="three" src="assets/images/banner/banner-bg-element.svg" alt="">
        </div>
    </section>

    <section class="rts-domain-pricing-area pt--30 pb--70"
             x-init="$wire.on('searchComplete', () => { loading = false; })">
        <div class="container">
            <div class="row justify-content-center">
                <div class="section-title-area w-full">

                </div>
            </div>
            <div class="section-inner" id="results" wire:loading.class="domain-search-loading">
                <div class="row g-5">
                    @if (session()->has('message'))
                        <div class="alert alert-success">
                            {{ session('message') }}
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($error)
                        <div class="alert alert-danger">
                            {{ $error }}
                        </div>
                    @endif

                    <div class="results-wrapper">
                        <!-- Alpine.js Loading Modal -->
                        <template x-teleport="body">
                            <div x-show="loading"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition ease-in duration-300"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="modal-overlay">
                                <div class="modal-content">
                                    <div class="spinner"></div>
                                    <h4 style="color: white; font-size: 1.8rem; margin-top: 1.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                        Searching Domains</h4>
                                    <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; margin-top: 0.5rem;">
                                        Please wait while we check domain availability...</p>
                                </div>
                            </div>
                        </template>
                        <div id="resultsContainer" class="col-lg-12">
                            @if($results && count($results) > 0)
                                @php
                                    // Find the primary domain
                                    $primaryDomain = null;
                                    $primaryResult = null;
                                    $suggestedDomains = [];

                                    foreach ($results as $domain => $result) {
                                        if ($result['is_primary'] ?? false) {
                                            $primaryDomain = $domain;
                                            $primaryResult = $result;
                                        } else {
                                            $suggestedDomains[$domain] = $result;
                                        }
                                    }
                                @endphp

                                @if($primaryDomain && $primaryResult)
                                    <!-- Main Requested Domain -->
                                    <div class="main-domain-result mb-4 pb--5">
                                        <div
                                            class="pricing-wrapper {{ $primaryResult['available'] ? 'available' : 'unavailable' }} p-4">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <p class="mb-2"><strong>{{ $primaryDomain }}</strong></p>
                                                    <span
                                                        class="domain-badge {{ $primaryResult['available'] ? 'available' : 'unavailable' }}">
                                                    {{ $primaryResult['available'] ? 'Available!' : 'Not Available' }}
                                                </span>
                                                </div>
                                                <div class="col-md-3 text-center">
                                                    @if ($primaryResult['available'])
                                                        <div class="price-area">
                                                            <span
                                                                class="now h4">{{ $primaryResult['formatted_price'] }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-3 text-end">
                                                    @if ($primaryResult['available'])
                                                        <button
                                                            wire:click="{{ $primaryResult['in_cart'] ? 'removeFromCart(\'' . $primaryDomain . '\')' : 'addToCart(\'' . $primaryDomain . '\', ' . $primaryResult['register_price'] . ', \'' . ($primaryResult['display_currency_code'] ?? $this->currentCurrency) . '\')' }}"
                                                            wire:loading.class="opacity-75"
                                                            wire:target="{{ $primaryResult['in_cart'] ? 'removeFromCart(\'' . $primaryDomain . '\')' : 'addToCart(\'' . $primaryDomain . '\', ' . $primaryResult['register_price'] . ', \'' . ($primaryResult['display_currency_code'] ?? $this->currentCurrency) . '\')' }}"
                                                            class="btn btn-lg text-white {{ $primaryResult['in_cart'] ? 'bg-danger' : 'bg-success' }} w-50">
                                                            <span wire:loading
                                                                  wire:target="{{ $primaryResult['in_cart'] ? 'removeFromCart(\'' . $primaryDomain . '\')' : 'addToCart(\'' . $primaryDomain . '\', ' . $primaryResult['register_price'] . ', \'' . ($primaryResult['display_currency_code'] ?? $this->currentCurrency) . '\')' }}"
                                                                  class="btn-spinner"></span>
                                                            {{ $primaryResult['in_cart'] ? 'Remove from cart' : 'Add to Cart' }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Suggested Domains -->
                                @if(count($suggestedDomains) > 0)
                                    <h4 class="pb--20 pt--20">Suggested Domains</h4>
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tbody>
                                            @foreach ($suggestedDomains as $domain => $result)
                                                <tr class="pricing-wrapper {{ $result['available'] ? 'available' : 'unavailable' }}">
                                                    <td class="align-middle" style="width: 40%">
                                                        <strong>{{ $domain }}</strong>
                                                        <div>
                                                            <span
                                                                class="status {{ $result['available'] ? 'available' : 'unavailable' }}">
                                                                {{ $result['available'] ? 'Available!' : 'Not Available' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle text-center" style="width: 30%">
                                                        @if ($result['available'])
                                                            <span class="now">{{ $result['formatted_price'] }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="align-middle text-end" style="width: 30%">
                                                        @if ($result['available'])
                                                            <button
                                                                wire:click="{{ $result['in_cart'] ? 'removeFromCart(\'' . $domain . '\')' : 'addToCart(\'' . $domain . '\', ' . $result['register_price'] . ', \'' . ($result['display_currency_code'] ?? $this->currentCurrency) . '\')' }}"
                                                                wire:loading.class="opacity-75"
                                                                wire:target="{{ $result['in_cart'] ? 'removeFromCart(\'' . $domain . '\')' : 'addToCart(\'' . $domain . '\', ' . $result['register_price'] . ', \'' . ($result['display_currency_code'] ?? $this->currentCurrency) . '\')' }}"
                                                                class="btn btn-lg text-white {{ $result['in_cart'] ? 'bg-danger' : 'bg-success' }} w-50"
                                                            >
                                                                <span wire:loading
                                                                      wire:target="{{ $result['in_cart'] ? 'removeFromCart(\'' . $domain . '\')' : 'addToCart(\'' . $domain . '\', ' . $result['register_price'] . ', \'' . ($result['display_currency_code'] ?? $this->currentCurrency) . '\')' }}"
                                                                      class="btn-spinner"></span>
                                                                {{ $result['in_cart'] ? 'Remove from cart' : 'Add to Cart' }}
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="container">
        <livewire:hosting-upsell />
    </div>
</div>
