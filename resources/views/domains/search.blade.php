<x-user-layout>
    @section('page-title')
        Register Domain
    @endsection

    @push('styles')
        <style>

            body.domain-page .domain-search-results .domain-result {
                font-family: "Inter", sans-serif !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 12px 0 !important;
                margin: 0 !important;
            }

            .domain-action-wrapper {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .domain-price {
                font-weight: 600;
                font-size: 1rem;
                color: #1f2937;
                white-space: nowrap;
            }

            .loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                border-radius: 12px;
            }

            .loading-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #e5e7eb;
                border-top: 4px solid #3b82f6;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 16px;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }

            .error-message {
                color: #dc2626;
                font-size: 0.875rem;
                margin-top: 5px;
                padding: 8px 12px;
                background-color: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 6px;
                font-weight: 500;
            }

            /* --- New styles for domain type selector --- */
            .domain-type-selector {
                margin-top: 15px;
                display: flex;
                justify-content: center;
                gap: 25px;
                color: #fff;
                font-weight: 500;
            }

            .domain-type-selector label {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }

            .domain-type-badge {
                display: inline-block;
                padding: 4px 10px;
                font-size: 0.8rem;
                font-weight: 600;
                border-radius: 12px;
                margin-left: 10px;
                vertical-align: middle;
            }
        </style>
    @endpush

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
                        <form id="domain-search-form" action="{{route('domains.search')}}" data-sal-delay="300"
                              data-sal-duration="800"
                              method="POST">
                            @csrf
                            <div class="rts-hero__form-area">
                                <input type="text" placeholder="find your domain name" name="domain" id="domain-input"
                                       value="{{ old('domain', $searchedDomain ?? '') }}" required>
                                <div class="select-button-area">
                                    <button type="submit" id="search-button">
                                        <span class="button-text">Search</span>
                                    </button>
                                </div>
                            </div>

                            {{-- New Domain Type Selector --}}
                            <div class="domain-type-selector">
                                <label>
                                    <input type="radio" name="domain_type"
                                           value="local" {{ old('domain_type', isset($domainType) ? $domainType->value : 'local') == 'local' ? 'checked' : '' }}>
                                    Local (.rw)
                                </label>
                                <label>
                                    <input type="radio" name="domain_type"
                                           value="international" {{ old('domain_type', isset($domainType) ? $domainType->value : 'local') == 'international' ? 'checked' : '' }}>
                                    International (.com, .net)
                                </label>
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
                            <p class="desc">Popular Domain:</p>
                            <ul class="tag-list">
                                <li><span>.com</span><span>$6.19</span></li>
                                <li><span>.net</span><span>$6.19</span></li>
                                <li><span>.org</span><span>$6.19</span></li>
                                <li><span>.info</span><span>$6.19</span></li>
                                <li><span>.xyz</span><span>$6.19</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(isset($details) || (isset($suggestions) && count($suggestions) > 0))
        <section class="mt-5 mb-5" id="search-results">
            <div class="container">
                <div class="domain-results-container" style="position: relative;">
                    <div class="domain-search-results">
                        <h1 class="results-title" style="font-size: 3rem;">Domain Search Results</h1>
                        @if(isset($errorMessage))
                            <div class="error-message" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif
                        @if(isset($details))
                            <div class="domain-result primary">
                                <div class="domain-info-box">
                                    <span class="domain">
                                        {{ $details['domain'] }}
                                        @if(isset($domainType))
                                            <span
                                                class="domain-type-badge {{ $domainType->color() }}">{{ $domainType->label() }}</span>
                                        @endif
                                    </span>
                                    <span
                                        class="domain-status {{ $details['available'] === 'true' ? 'available' : 'taken' }}">
                                        {{ $details['available'] === 'true' ? 'Available' : 'Taken' }}
                                    </span>
                                </div>
                                <livewire:domain-cart-button
                                    :domain="$details['domain']"
                                    :price="$details['price']"
                                    :available="$details['available'] === 'true'"
                                />
                            </div>
                        @endif
                        @if(isset($suggestions) && count($suggestions) > 0)
                            <h2 class="suggestions-title">Suggested Domains</h2>
                            @foreach($suggestions as $suggestion)
                                <div class="domain-result">
                                    <div class="domain-info-box">
                                        <span class="domain">{{ $suggestion['domain'] }}</span>
                                        <span
                                            class="domain-status {{ $suggestion['available'] === 'true' ? 'available' : 'taken' }}">
                                            {{ $suggestion['available'] === 'true' ? 'Available' : 'Taken' }}
                                        </span>
                                    </div>
                                    <livewire:domain-cart-button
                                        :domain="$suggestion['domain']"
                                        :price="$suggestion['price']"
                                        :available="$suggestion['available'] === 'true'"
                                    />
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

</x-user-layout>
