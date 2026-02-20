@php
    $selectedCurrency = $selectedCurrency ?? \App\Helpers\CurrencyHelper::getUserCurrency();
    $domainComparePrices = $domainComparePrices ?? ['com' => null, 'net' => null, 'info' => null, 'org' => null];
@endphp
<x-user-layout>
    @section('page-title')
        Home
    @endsection
    <section class="rts-hero rts-hero__one banner-style-home-one">
        <div class="container">
            <div class="rts-hero__blur-area"></div>
            <div class="row align-items-end position-relative">
                <div class="col-lg-6">
                    <div class="rts-hero__content w-550">

                        <h1 class="heading" data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">
                            Premium
                            Hosting
                            Technologies
                        </h1>
                        <p class="description" data-sal="slide-down" data-sal-delay="400" data-sal-duration="800">
                            Developing smart solutions in-house and adopting the latest speed and security technologies
                            is our passion.</p>
                        <div class="rts-hero__content--group" data-sal="slide-down" data-sal-delay="500"
                            data-sal-duration="800">
                            <a href="{{ route('register') }}" class="primary__btn white__bg">Get Started <i
                                    class="bi bi-arrow-right"></i></a>
                            {{-- <a href="{{ route('hosting.categories.index') }}" class="btn__zero plan__btn">Plans & Pricing <i
                                    class="bi bi-arrow-right"></i></a> --}}
                        </div>

                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="rts-hero__images position-relative">
                        <div class="rts-hero-main">
                            <div class="image-main ">
                                <img class="main top-bottom2" src="assets/images/banner/hosting-01.svg" alt="">
                            </div>
                            <img class="hero-shape one" src="assets/images/banner/hosting.svg" alt="">
                        </div>
                        <div class="rts-hero__images--shape">
                            <div class="one top-bottom">
                                <img src="assets/images/banner/left.svg" alt="">
                            </div>
                            <div class="two bottom-top">
                                <img src="assets/images/banner/left.svg" alt="">
                            </div>
                            <div class="three top-bottom">
                                <img src="assets/images/banner/top.svg" alt="">
                            </div>
                            <div class="four bottom-top">
                                <img src="assets/images/banner/right.svg" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- HERO BANNER ONE END -->

    <!-- BRAND AREA -->
    <div class="rts-brand rts-brand__bg--section pt-100 pb-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="rts-brand__wrapper">
                        <div class="rts-brand__wrapper--text">
                            <h5 data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">Hosting solutions
                                trusted by the owners of <span>2,800,000</span> domains.</h5>
                            <div class="rts-brand__wrapper--text-review" data-sal="slide-down" data-sal-delay="400"
                                data-sal-duration="800">
                                <div class="review">
                                    <div class="star">Excellent <img src="assets/images/brand/review-star.svg"
                                            alt="">
                                    </div>
                                </div>
                                <div class="review__company">
                                    954 reviews on <img src="assets/images/brand/trust-pilot.svg" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="rts-brand__slider">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img src="assets/images/brand/01.svg"
                                                alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img src="assets/images/brand/02.svg"
                                                alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img src="assets/images/brand/03.svg"
                                                alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img src="assets/images/brand/04.svg"
                                                alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img src="assets/images/brand/05.svg"
                                                alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img
                                                src="assets/images/brand/06.svg" alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"> <img
                                                src="assets/images/brand/07.svg" alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img
                                                src="assets/images/brand/08.svg" alt=""></a>
                                    </div>
                                </div>
                                <div class="swiper-slide">
                                    <div class="rts-brand__slider--single">
                                        <a href="#" aria-label="brand-link"><img
                                                src="assets/images/brand/01.svg" alt=""></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- BRAND AREA END-->

    <!-- HOSTING OPTION -->
<livewire:home-hosting-option-component :hosting-categories="$hostingCategories" :selected-currency="$selectedCurrency" />
    <!-- HOSTING OPTION END -->

    <!-- ABOUT US -->
    <div class="rts-about position-relative section__padding">
        <div class="container">
            <div class="row">
                <div class="col-xl-6 col-lg-6">
                    <div class="rts-about__image">
                        <img src="assets/images/about/about-big.png" alt="">
                        <img src="assets/images/about/about-shape-01.svg" alt=""
                            class="shape one right-left">
                        <img src="assets/images/about/about-shape-02.svg" alt="" class="shape two">
                    </div>
                </div>
                <div class="col-xl-5 col-lg-6">
                    <div class="rts-about__content">
                        <h3 data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">We build Our Business
                            For Your Success.
                        </h3>
                        <p class="description" data-sal="slide-down" data-sal-delay="400" data-sal-duration="800">
                            Whether you need a simple blog, want to highlight your
                            business, sell products through an eCommerce.
                        </p>
                        <div class="rts-about__content--single" data-sal="slide-down" data-sal-delay="500"
                            data-sal-duration="800">
                            <div class="image">

                                <img src="assets/images/about/01.svg" alt="">
                            </div>
                            <div class="description">
                                <h6>Web Hosting</h6>
                                <p>The most popular hosting plan available and comes at one of the most affordable price
                                    points.</p>
                            </div>
                        </div>
                        <div class="rts-about__content--single" data-sal="slide-down" data-sal-delay="600"
                            data-sal-duration="800">
                            <div class="image bg-2">
                                <img src="assets/images/about/02.svg" alt="">
                            </div>
                            <div class="description">
                                <h6>Managed WordPress Hosting</h6>
                                <p>Our Managed WordPress Hosting gives you speed and performance with a full set of
                                    features.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="rts-about-shape"></div>
    </div>
    <!-- ABOUT US END -->

    <!-- SEARCH DOMAIN -->
   <livewire:home-tld-component />
    <!-- SEARCH DOMAIN END -->

    <!-- OUR SERVICES -->
    <section class="rts-service section__padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="rts-section text-center w-530">
                    <h3 class="rts-section__title" data-sal="slide-down" data-sal-delay="300"
                        data-sal-duration="800">We
                        Provide Hosting Solution</h3>
                    <p class="rts-section__description" data-sal="slide-down" data-sal-delay="400"
                        data-sal-duration="800">Select your solution and we will help you narrow down our best
                        high-speed options to fit your needs.
                    </p>
                </div>
            </div>
            <!-- service list -->
            <div class="row">
                <div class="rts-service__wrapper">
                    @foreach ($hostingCategories->split(4) as $chunk)
                        <div class="rts-service__column">
                            @foreach ($chunk as $category)
                                <div class="rts-service__single">
                                    <div class="rts-service__single--icon shared__hosting">
                                        @if ($category->slug == 'shared-hosting')
                                            <img src="assets/images/service/shared__hosting.svg" alt="">
                                        @elseif ($category->slug == 'managed-wordpress-hosting')
                                            <img src="assets/images/service/managed__wordpress__hosting.svg" alt="">
                                        @elseif ($category->slug == 'dedicated-hosting')
                                            <img src="assets/images/service/dedicated__hosting.svg" alt="">
                                        @elseif ($category->slug == 'vps-hosting')
                                            <img src="assets/images/service/vps__hosting.svg" alt="">
                                        @elseif ($category->slug == 'reseller-hosting')
                                            <img src="assets/images/service/resseller__hosting.svg" alt="">
                                        @elseif ($category->slug == 'cloud-hosting')
                                            <img src="assets/images/service/cloud__hosting.svg" alt="">
                                        @else
                                            <img src="assets/images/hosting/01.svg" alt="">
                                        @endif
                                    </div>
                                    <a href="{{ route('hosting.categories.show', $category->slug) }}"
                                        class="rts-service__single--title">{{ $category->name }}</a>
                                    <p class="rts-service__single--excerpt">
                                        {{ Str::limit($category->description, 60) }}
                                    </p>
                                    <a href="{{ route('hosting.categories.show', $category->slug) }}"
                                        class="rts-service__single--btn">View Details <i
                                            class="bi bi-arrow-right"></i></a>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    <!-- OUR SERVICES END -->

    <!-- FLASH SELL AREA -->
    <section class="rts-flash-sell">
        <div class="container">
            <div class="rts-flash-sell__bg">
                <div class="row align-items-center">
                    <div class="col-lg-4">
                        <div class="rts-flash-sell__title">
                            <h3 data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">Hosting Flash Sale
                            </h3>
                            <p data-sal="slide-down" data-sal-delay="400" data-sal-duration="800">For a limited time,
                                launch your website
                                with incredible savings.
                            </p>
                            <a data-sal="slide-down" data-sal-delay="500" data-sal-duration="800" href="#"
                                class="primary__btn white__bg">See Details</a>
                        </div>
                    </div>
                    <div class="col-lg-8 p--0">
                        <div class="row">
                            <div class="col-lg-6 col-md-6">
                                <div class="single__sell">
                                    <div class="single__sell--content">
                                        <div class="offer">for a limited Time</div>
                                        <div class="discount">67% Off</div>
                                        <span>hosting</span>
                                    </div>
                                    <div class="single__sell--image">
                                        <img src="assets/images/icon/cloud.svg" alt="">
                                    </div>

                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <div class="single__sell">
                                    <div class="single__sell--content">
                                        <div class="offer">for a limited Time</div>
                                        <div class="discount">90% Off</div>
                                        <span>hosting</span>
                                    </div>
                                    <div class="single__sell--image">
                                        <img src="assets/images/icon/domain.svg" alt="">
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- FLASH SELL AREA END -->


    <!-- WHY CHOOSE US -->
    <section class="rts-whychoose section__padding">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 order-change">
                    <div class="rts-whychoose__content">
                        <h3 class="rts-whychoose__content--title" data-sal="slide-down" data-sal-delay="300"
                            data-sal-duration="800">
                            Why Choose {{ config('app.name') }} for Your Hosting Needs
                        </h3>

                        <!-- single content-->
                        <div class="single" data-sal="slide-right" data-sal-delay="300" data-sal-duration="800">
                            <div class="single__image">
                                <img src="assets/images/icon/speed.svg" alt="">
                            </div>
                            <div class="single__content">
                                <h6>Up To 20xFaster Turbo</h6>
                                <p>{{ config('app.name') }} is a high-performance hosting provider that offers up to 20x faster turbo speeds. This means better SEO rankings, lower bounce rates & higher conversion rates!</p>
                            </div>
                        </div>
                        <!-- single content-->
                        <div class="single" data-sal="slide-right" data-sal-delay="400" data-sal-duration="800">
                            <div class="single__image bg1">
                                <img src="assets/images/icon/support.svg" alt="">
                            </div>
                            <div class="single__content">
                                <h6>Guru Crew Support</h6>
                                <p>{{ config('app.name') }} has a knowledgeable and friendly support team
                                    is available 24/7/365 to help!</p>
                            </div>
                        </div>
                        <!-- single content-->
                        <div class="single" data-sal="slide-right" data-sal-delay="500" data-sal-duration="800">
                            <div class="single__image">
                                <img src="assets/images/icon/money-back.svg" alt="">
                            </div>
                            <div class="single__content">
                                <h6>Money-Back Guarantee</h6>
                                <p>Give {{ config('app.name') }} high-speed hosting service a try
                                    completely risk-free!</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="rts-whychoose__image">
                        <img src="assets/images/whychoose.svg" alt="">
                        <img src="assets/images/paper-plane.svg" alt="" class="shape one bottom-top">
                        <img src="assets/images/wifi.svg" alt="" class="shape two right-left">
                    </div>
                </div>
            </div>
        </div>
        <div class="rts-shape">
            <div class="rts-shape__one"></div>
        </div>
    </section>
    <!-- WHY CHOOSE US END -->

    <!-- HOSTING PLAN -->
    <livewire:home-hosting-component :hosting-plans="$hostingPlans" :selected-currency="$selectedCurrency" />
    <!-- HOSTING PLAN END -->
    <!-- FAQ -->
    <section class="rts-faq section__padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <div class="rts-faq__first">
                        <h3 class="title" data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">
                            Got
                            questions? Well,
                            we've got answers.</h3>
                        <p data-sal="slide-down" data-sal-delay="400" data-sal-duration="800">From 24/7 support
                            that
                            acts as your extended team to incredibly fast website performance</p>
                        <img data-sal="slide-down" data-sal-delay="500" data-sal-duration="800"
                            src="assets/images/faq/faq.svg" alt="faq">
                        <div class="rts-faq__first--shape">
                            <div class="img"><img src="assets/images/faq/faq__animated.svg" alt="">
                            </div>
                            <div class="shape-one">domain</div>
                            <div class="shape-two">hosting</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 offset-lg-1">
                    <div class="rts-faq__accordion">
                        <div class="accordion accordion-flush" id="rts-accordion">
                            <div class="accordion-item active" data-sal="slide-left" data-sal-delay="300"
                                data-sal-duration="800">
                                <div class="accordion-header" id="first">
                                    <h4 class="accordion-button collapse show" data-bs-toggle="collapse"
                                        data-bs-target="#item__one" aria-expanded="false" aria-controls="item__one">
                                        Why buy a domain name from hostie?
                                    </h4>
                                </div>
                                <div id="item__one" class="accordion-collapse collapse show" aria-labelledby="first"
                                    data-bs-parent="#rts-accordion">
                                    <div class="accordion-body">
                                        Above all else, we strive to deliver outstanding customer experiences. When you
                                        buy a domain name from hostie, we guarantee it will be handed over.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item" data-sal="slide-left" data-sal-delay="400"
                                data-sal-duration="800">
                                <div class="accordion-header" id="two">
                                    <h4 class="accordion-button collapsed" data-bs-toggle="collapse"
                                        data-bs-target="#item__two" aria-expanded="false" aria-controls="item__two">
                                        How does domain registration work?
                                    </h4>
                                </div>
                                <div id="item__two" class="accordion-collapse collapse" aria-labelledby="two"
                                    data-bs-parent="#rts-accordion">
                                    <div class="accordion-body">
                                        Above all else, we strive to deliver outstanding customer experiences. When you
                                        buy a domain name from hostie, we guarantee it will be handed over.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item" data-sal="slide-left" data-sal-delay="500"
                                data-sal-duration="800">
                                <div class="accordion-header" id="three">
                                    <h4 class="accordion-button collapsed" data-bs-toggle="collapse"
                                        data-bs-target="#item__three" aria-expanded="false"
                                        aria-controls="item__three">
                                        Why is domain name registration required?
                                    </h4>
                                </div>
                                <div id="item__three" class="accordion-collapse collapse" aria-labelledby="three"
                                    data-bs-parent="#rts-accordion">
                                    <div class="accordion-body">
                                        Above all else, we strive to deliver outstanding customer experiences. When you
                                        buy a domain name from hostie, we guarantee it will be handed over.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item" data-sal="slide-left" data-sal-delay="600"
                                data-sal-duration="800">
                                <div class="accordion-header" id="four">
                                    <h4 class="accordion-button collapsed" data-bs-toggle="collapse"
                                        data-bs-target="#item__four" aria-expanded="false"
                                        aria-controls="item__four">
                                        Why is domain name registration required?
                                    </h4>
                                </div>
                                <div id="item__four" class="accordion-collapse collapse" aria-labelledby="four"
                                    data-bs-parent="#rts-accordion">
                                    <div class="accordion-body">
                                        Above all else, we strive to deliver outstanding customer experiences. When you
                                        buy a domain name from hostie, we guarantee it will be handed over.
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- FAQ END -->
    <!-- CTA AREA -->
    <div class="rts-cta">
        <div class="container">
            <div class="row">
                <div class="rts-cta__wrapper">
                    <div class="rts-cta__left">
                        <h3 class="cta__title" data-sal="slide-down" data-sal-delay="300" data-sal-duration="800">
                            Experience the Hostie Hosting Difference Today!</h3>
                        <p data-sal="slide-down" data-sal-delay="400" data-sal-duration="800">Above all else, we
                            strive
                            deliver outstanding customer experiences When you buy a domain name from.</p>
                        <a data-sal="slide-down" data-sal-delay="500" data-sal-duration="800" href="#"
                            class="primary__btn secondary__bg">get started <i
                                class="fa-regular fa-arrow-right"></i></a>
                    </div>
                    <div class="rts-cta__right">
                        <div class="cta-image">
                            <div class="cta-image__one">
                                <img src="assets/images/cta/cta__hosting.svg" alt="">
                            </div>
                            <div class="cta-image__two">
                                <img src="assets/images/cta/cta__person.svg" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CTA AREA END  -->

</x-user-layout>
