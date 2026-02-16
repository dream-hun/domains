<x-user-layout>
    @section('page-title')
        Register Domain
    @endsection

    @push('styles')
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
                        <form id="domain-search-form" action="{{ route('domains.search') }}" data-sal-delay="300"
                            data-sal-duration="800" method="POST">
                            @csrf
                            <div class="rts-hero__form-area">
                                <input type="text" placeholder="find your domain name" name="domain"
                                    id="domain-input" value="{{ old('domain', $searchedDomain ?? '') }}" required>
                                <div class="select-button-area">
                                    <button type="submit" id="search-button">
                                        <span class="button-text">Search</span>
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
                                @if (isset($popularDomains))
                                    @foreach (array_merge($popularDomains['local'] ?? [], $popularDomains['international'] ?? []) as $domain)
                                        <li><span>{{ $domain['tld'] }}</span><span>{{ $domain['price'] }}</span></li>
                                    @endforeach
                                @else
                                    <li><span>.com</span><span>$12.99</span></li>
                                    <li><span>.net</span><span>$14.99</span></li>
                                    <li><span>.org</span><span>$13.99</span></li>
                                    <li><span>.rw</span><span>15,000 RWF</span></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        {{--<livewire:hosting-upsell />--}}
    </div>

    @if (isset($details) || (isset($suggestions) && count($suggestions) > 0))
        <section class="mt-5 mb-5" id="search-results">
            <div class="container">
                <div class="domain-results-container" style="position: relative;">
                    <div class="domain-search-results">
                        <h1 class="results-title" style="font-size: 3rem; font-family: 'Inter', sans-serif;">Domain
                            Search Results</h1>
                        @if (isset($errorMessage))
                            <div class="error-message" style="margin-bottom: 20px;">{{ $errorMessage }}</div>
                        @endif
                        @if (isset($details))
                            <div class="domain-result primary">
                                <div class="domain-info-box">
                                    <span class="domain">{{ $details['domain'] }}</span>
                                    {{-- <span class="domain-price">{{ $details['price'] }}/year</span> --}}
                                </div>
                                <div class="domain-actions">
                                    <livewire:domain-cart-button :domain="$details['domain']" :price="$details['price']"
                                        :available="$details['available'] === 'true'" />
                                </div>
                            </div>
                        @endif
                        @if (isset($suggestions) && count($suggestions) > 0)
                            <h2 class="suggestions-title">Other Suggestions</h2>
                            @foreach ($suggestions as $suggestion)
                                <div class="domain-result">
                                    <div class="domain-info-box">
                                        <span class="domain">{{ $suggestion['domain'] }}</span>
                                         <span class="domain-price">{{ $suggestion['price'] }}/year</span>
                                    </div>
                                    <div class="domain-actions">
                                        <livewire:domain-cart-button :domain="$suggestion['domain']" :price="$suggestion['price']"
                                            :available="$suggestion['available'] === 'true'" />
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
</x-user-layout>
