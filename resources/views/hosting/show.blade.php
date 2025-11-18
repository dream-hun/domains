<x-user-layout>
    @section('page-title')
        {{ $category->name }}
    @endsection
    @section('page-description')
        {{ $category->description }}
    @endsection
    <div class="rts-hosting-banner rts-hosting-banner-bg banner-default-height">
        <div class="container">
            <div class="row justify-content-sm-center">
                <div class="col-lg-12">
                    <div class="banner-area">
                        <div class="rts-hosting-banner rts-hosting-banner__content">
                            <span class="starting__price sal-animate" data-sal="slide-down" data-sal-delay="100"
                                data-sal-duration="800">Starting at $2.59/mo</span>
                            <h1 class="banner-title sal-animate" data-sal="slide-down" data-sal-delay="300"
                                data-sal-duration="800">
                                {{ $category->name }}
                            </h1>
                            <p class="slogan sal-animate" data-sal="slide-down" data-sal-delay="400"
                                data-sal-duration="800">{{ $category->description }}</p>
                            <div class="hosting-feature sal-animate" data-sal="slide-down" data-sal-delay="500"
                                data-sal-duration="800">
                                <div class="hosting-feature__single">
                                    <div class="icon-image">
                                        <img src="assets/images/banner/shared/diamond.png" alt="">
                                    </div>
                                    <p class="feature-text">

                                    </p>
                                </div>
                                <div class="hosting-feature__single">
                                    <div class="icon-image">
                                        <img src="assets/images/banner/shared/wordpress.png" alt="">
                                    </div>
                                    <p class="feature-text">
                                        Look like a Pri- Fast,
                                        Secure, &amp; Always Up
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="rts-hosting-banner__image">
                            <img src="assets/images/banner/shared/shared__hosting.svg" alt="">
                            <img class="shape-image one right-left"
                                src="assets/images/banner/shared/shared__hosting-sm1.svg" alt="">
                            <img class="shape-image two pulsing"
                                src="assets/images/banner/shared/shared__hosting-sm2.svg" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="rts-pricing-plan card-plan-bg page-bg section__padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="rts-section w-490 text-center">
                    <h2 class="rts-section__title sal-animate" data-sal="slide-down" data-sal-delay="100"
                        data-sal-duration="800">Choose Hosting Plan</h2>
                    <p class="rts-section__description sal-animate" data-sal="slide-down" data-sal-delay="300"
                        data-sal-duration="800">Globally incubate next-generation e-services via state <br> of the art
                        technology.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="row justify-content-center sal-animate" data-sal="slide-down" data-sal-delay="400"
                    data-sal-duration="800">
                    <div class="col-lg-4 col-md-5">
                        <div class="rts-pricing-plan__tab plan__tab">
                            <div class="tab__button">
                                <div class="tab__button__item">
                                    <button class="tab__price active" data-tab="monthly">monthly</button>
                                    <button class="tab__price" data-tab="yearly">yearly</button>
                                </div>
                            </div>
                            <div class="discount">
                                <span class="line"><img src="{{ asset('assets/images/pricing/offer__vactor.svg') }}"
                                        height="20" width="85" alt=""></span>
                                <p>20% save</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- PRICING PLAN -->
                <div class="price__content open" id="monthly" style="">
                    <div class="row g-30 monthly">
                        @foreach ($plans as $plan)
                            @php
                                $monthlyPrice = $plan->planPrices->firstWhere('billing_cycle', 'monthly');
                            @endphp
                            @if ($monthlyPrice)
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="card-plan active">
                                        @if ($plan->is_popular)
                                            <div class="popular-tag">most popular</div>
                                        @endif
                                        <div class="card-plan__package">
                                            <div class="icon">
                                                <img src="assets/images/pricing/business.svg" height="30"
                                                    width="30" alt="">
                                            </div>
                                            <h4 class="package__name">{{ $plan->name }}</h4>
                                        </div>
                                        <p class="card-plan__desc">{{ $plan->description }}</p>
                                        @if ($monthlyPrice->discount_percentage)
                                        <div class="card-plan__offer">
                                            <span class="past-price">{{ $monthlyPrice->regular_price }}</span>
                                                <span class="offer-given">Save
                                                    {{ $monthlyPrice->discount_percentage }}%</span>
                                            </div>
                                        @endif
                                        <h5 class="card-plan__price">
                                            <sup>$</sup> {{ $monthlyPrice->regular_price }} <sub>/ month</sub>
                                        </h5>
                                        <div class="card-plan__cartbtn">
                                            <a href="#">add to cart</a>
                                        </div>
                                        <p class="card-plan__renew-price">
                                            $ {{ $monthlyPrice->renewal_price }} /Month when you renew
                                        </p>
                                        <div class="card-plan__feature">
                                            <ul class="card-plan__feature--list">
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> 1
                                                        Website</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Explore, discover, and learn on our innovative and informative website."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> Standard
                                                        Performance</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Unlock superior online experiences with our standard performance solutions, ensuring reliability, speed, and seamless functionality for your website needs."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> 24/7/365
                                                        Support</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Hostie provides reliable 24/7 support for your hosting needs, ensuring assistance whenever you require help."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-xmark"></i> Free
                                                        Email</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Hostie offers complimentary email services, empowering your online communication with reliable and secure free email solutions."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-xmark"></i> Unlimited
                                                        Bandwidth</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Hostie provides unlimited bandwidth, ensuring seamless data transfer for your website's optimal performance and user experience."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> 100 GB
                                                        SSD
                                                        Storage</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Elevate your online presence with Hostie, offering unlimited bandwidth for your domain, ensuring optimal performance and seamless data flow."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>


                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> Unlimited
                                                        Free SSL</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Secure your website with Hostie's unlimited free SSL certificates, ensuring encrypted and safe online transactions for your users."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> 99.9%
                                                        Uptime
                                                        Guarantee</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Hostie guarantees 99% uptime, ensuring your website is consistently available and reliable for visitors around the clock."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> Web
                                                        Application Firewall</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Enhance your website's security with Hostie's Web Application Firewall, protecting against online threats and ensuring a safe online environment."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-trigered">
                                                    <span class="text">More Features <i
                                                            class="fa-sharp fa-regular fa-chevron-down"></i>
                                                    </span>
                                                </li>
                                            </ul>
                                            <ul class="card-plan__feature--list more__feature">
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> 100 GB
                                                        SSD
                                                        Storage</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Hostie offers generous hosting with 100GB SSD storage, providing ample space for your data and ensuring high-performance storage solutions."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> Unlimited
                                                        Free SSL</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Secure your website with Hostie's unlimited free SSL certificates, ensuring encrypted and safe online transactions for your users."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-check"></i> 99.9%
                                                        Uptime
                                                        Guarantee</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Hostie guarantees 99% uptime, ensuring your website is consistently available and reliable for visitors around the clock."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-item">
                                                    <span class="text"><i class="fa-regular fa-xmark"></i> Web
                                                        Application Firewall</span>
                                                    <span class="tolltip" data-bs-toggle="tooltip"
                                                        data-bs-placement="bottom"
                                                        data-bs-original-title="Enhance your website's security with Hostie's Web Application Firewall, protecting against online threats and ensuring a safe online environment."><i
                                                            class="fa-light fa-circle-question"></i></span>
                                                </li>
                                                <li class="card-plan__feature--list-trigered">
                                                    <span class="text">See less Features <i
                                                            class="fa-sharp fa-regular fa-chevron-up"></i>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- PRICING PLAN -->
            <div class="price__content" id="yearly" style="display: none;">
                <div class="row g-30 yearly">
                    @foreach ($plans as $plan)
                        @php
                            $yearlyPrice = $plan->planPrices->firstWhere('billing_cycle', 'annually');
                        @endphp
                        @if ($yearlyPrice)
                            <div class="col-lg-4 col-md-6 col-sm-6">
                                <div class="card-plan active">
                                    @if ($plan->is_popular)
                                        <div class="popular-tag">most popular</div>
                                    @endif
                                    <div class="card-plan__package">
                                        <div class="icon">
                                            <img src="assets/images/pricing/business.svg" height="30"
                                                width="30" alt="">
                                        </div>
                                        <h4 class="package__name">{{ $plan->name }}</h4>
                                    </div>
                                    <p class="card-plan__desc">{{ $plan->description }}</p>
                                    @if ($yearlyPrice->discount_percentage)
                                    <div class="card-plan__offer">
                                        <span class="past-price">{{ $yearlyPrice->regular_price }}</span>
                                            <span class="offer-given">Save
                                                {{ $yearlyPrice->discount_percentage }}%</span>
                                        </div>
                                    @endif
                                    <h5 class="card-plan__price">
                                        <sup>$</sup> {{ $yearlyPrice->regular_price }} <sub>/ Year</sub>
                                    </h5>
                                    <div class="card-plan__cartbtn">
                                        <a href="#">add to cart</a>
                                    </div>
                                    <p class="card-plan__renew-price">
                                        $ {{ $yearlyPrice->renewal_price }} /Year when you renew
                                    </p>
                                    <div class="card-plan__feature">
                                        <ul class="card-plan__feature--list">
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> 1
                                                    Website</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Explore, discover, and learn on our innovative and informative website."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> Standard
                                                    Performance</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Unlock superior online experiences with our standard performance solutions, ensuring reliability, speed, and seamless functionality for your website needs."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> 24/7/365
                                                    Support</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Hostie provides reliable 24/7 support for your hosting needs, ensuring assistance whenever you require help."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-xmark"></i> Free
                                                    Email</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Hostie offers complimentary email services, empowering your online communication with reliable and secure free email solutions."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-xmark"></i> Unlimited
                                                    Bandwidth</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Hostie provides unlimited bandwidth, ensuring seamless data transfer for your website's optimal performance and user experience."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> 100 GB SSD
                                                    Storage</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Elevate your online presence with Hostie, offering unlimited bandwidth for your domain, ensuring optimal performance and seamless data flow."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>


                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> Unlimited
                                                    Free
                                                    SSL</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Secure your website with Hostie's unlimited free SSL certificates, ensuring encrypted and safe online transactions for your users."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> 99.9% Uptime
                                                    Guarantee</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Hostie guarantees 99% uptime, ensuring your website is consistently available and reliable for visitors around the clock."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> Web
                                                    Application
                                                    Firewall</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Enhance your website's security with Hostie's Web Application Firewall, protecting against online threats and ensuring a safe online environment."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-trigered">
                                                <span class="text">More Features <i
                                                        class="fa-sharp fa-regular fa-chevron-down"></i>
                                                </span>
                                            </li>
                                        </ul>
                                        <ul class="card-plan__feature--list more__feature">
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> 100 GB SSD
                                                    Storage</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Hostie offers generous hosting with 100GB SSD storage, providing ample space for your data and ensuring high-performance storage solutions."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> Unlimited
                                                    Free
                                                    SSL</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Secure your website with Hostie's unlimited free SSL certificates, ensuring encrypted and safe online transactions for your users."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-check"></i> 99.9% Uptime
                                                    Guarantee</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Hostie guarantees 99% uptime, ensuring your website is consistently available and reliable for visitors around the clock."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-item">
                                                <span class="text"><i class="fa-regular fa-xmark"></i> Web
                                                    Application
                                                    Firewall</span>
                                                <span class="tolltip" data-bs-toggle="tooltip"
                                                    data-bs-placement="bottom"
                                                    data-bs-original-title="Enhance your website's security with Hostie's Web Application Firewall, protecting against online threats and ensuring a safe online environment."><i
                                                        class="fa-light fa-circle-question"></i></span>
                                            </li>
                                            <li class="card-plan__feature--list-trigered">
                                                <span class="text">See less Features <i
                                                        class="fa-sharp fa-regular fa-chevron-up"></i>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div class="view-plan-btn">
            <a href="pricing.html" class="btn long-btn">view all plan</a>
        </div>
    </div>
    </div>
</x-user-layout>
