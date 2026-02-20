<div class="domain-page">
    <style>
        .domain-search-results {
            width: 100%;
        }

        .domain-result {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
            font-family: "Inter", sans-serif;
            font-size: 16px;
            line-height: 26px;
            gap: 20px;
            min-height: 60px;
            flex-wrap: nowrap;
        }

        .domain-result:last-child {
            border-bottom: none;
        }

        .domain-result.primary {
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f8fafc;
        }

        .domain-info-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            min-width: 0;
        }

        .domain {
            font-size: 16px;
            line-height: 26px;
            font-weight: 600;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .domain-price {
            font-size: 16px;
            line-height: 26px;
            color: #6b7280;
            font-weight: 400;
        }

        .domain-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .register-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 16px;
            line-height: 26px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .register-btn:hover {
            background-color: #2563eb;
        }

        .register-btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .suggestions-title {
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            line-height: 26px;
            font-weight: 600;
            color: #374151;
            margin: 30px 0 20px 0;
        }

        .domain-page .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 5px;
            padding: 8px 12px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>

    <section class="rts-hero-three rts-hero__one rts-hosting-banner domain-checker-padding banner-default-height">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <div class="rts-hero__content domain">
                        <h1 data-sal="slide-down" data-sal-delay="100" data-sal-duration="800" class="sal-animate">Find
                            Best Unique Domains
                            you need!
                        </h1>
                        <p class="description sal-animate" data-sal="slide-down" data-sal-delay="200"
                            data-sal-duration="800">Web
                            Hosting, Domain Name and Hosting Center Solutions</p>
                        <form wire:submit.prevent="search" id="domain-search-form" data-sal-delay="300"
                            data-sal-duration="800">
                            <div class="rts-hero__form-area">
                                <input type="text" placeholder="find your domain name"
                                    wire:model.defer="searchedDomain"
                                    id="domain-input" required>
                                <div class="select-button-area">
                                    <button type="submit" id="search-button" class="register-btn"
                                        wire:loading.attr="disabled" wire:target="search">
                                        <span wire:loading.remove wire:target="search" class="button-text">Search</span>
                                        <span wire:loading wire:target="search">Searching...</span>
                                    </button>
                                </div>
                            </div>

                            @if ($errors->any())
                                <div class="validation-errors" style="margin-top: 15px;">
                                    @foreach ($errors->all() as $error)
                                        <div class="error-message">
                                            {{ $error }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </form>
                        <div class="banner-content-tag" data-sal-delay="400" data-sal-duration="800">
                            <p class="desc">Popular Domains:</p>
                            <ul class="tag-list">
                                @php
                                    $allPopular = array_merge($popularDomains['local'] ?? [], $popularDomains['international'] ?? []);
                                @endphp
                                @forelse ($allPopular as $domain)
                                    <li><span>{{ $domain['tld'] }}</span><span>{{ $domain['price'] }}</span></li>
                                @empty
                                    <li class="text-muted">No popular TLDs configured.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($details !== null || count($suggestions) > 0)
        <section class="mt-5 mb-5" id="search-results">
            <div class="container">
                <div class="domain-results-container" style="position: relative;">
                    <div class="domain-search-results">
                        <h1 class="results-title" style="font-size: 3rem; font-family: 'Inter', sans-serif;">Domain
                            Search Results</h1>
                        @if ($errorMessage)
                            <div class="error-message" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif
                        @if ($details !== null)
                            <div class="domain-result primary">
                                <div class="domain-info-box">
                                    <span class="domain">{{ $details['domain'] }}</span>
                                </div>
                                <div class="domain-actions">
                                    <livewire:domain-cart-button
                                        :domain="$details['domain']"
                                        :price="$this->getDisplayPriceForItem($details)"
                                        :available="$details['available'] === 'true'"
                                        :tld-id="$details['tld_id'] ?? null"
                                        :currency="$selectedCurrency"
                                    />
                                </div>
                            </div>
                        @endif
                        @if (count($suggestions) > 0)
                            <h2 class="suggestions-title">Other Suggestions</h2>
                            @foreach ($suggestions as $suggestion)
                                <div class="domain-result">
                                    <div class="domain-info-box">
                                        <span class="domain">{{ $suggestion['domain'] }}</span>
                                        <span class="domain-price">{{ $this->getDisplayPriceForItem($suggestion) }}/year</span>
                                    </div>
                                    <div class="domain-actions">
                                        <livewire:domain-cart-button
                                            :domain="$suggestion['domain']"
                                            :price="$this->getDisplayPriceForItem($suggestion)"
                                            :available="$suggestion['available'] === 'true'"
                                            :tld-id="$suggestion['tld_id'] ?? null"
                                            :currency="$selectedCurrency"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    <livewire:cart-summary />
</div>
