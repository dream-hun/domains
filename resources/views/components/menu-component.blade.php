<!-- HEADER AREA -->
<header class="rts-header style-one header__default">
    <!-- HEADER TOP AREA -->
    <div class="rts-ht rts-ht__bg">
        <div class="container">
            <div class="row">
                <div class="rts-ht__wrapper">
                    <div class="rts-ht__email">
                        <a href="{{--mailto:{{ $settings->email }}--}}">
                            <img src="assets/images/icon/email.svg" alt="" class="icon">{{--{{ $settings->email }}--}}
                        </a>
                    </div>
                    <div class="rts-ht__links">
                        <div class="live-chat-has-dropdown">
                            <a href="#" class="live__chat">
                                <img src="assets/images/icon/forum.svg" alt="" class="icon">Live Chat
                            </a>
                        </div>
                        <livewire:cart-total/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- HEADER TOP AREA END -->
    <div class="container">
        <div class="row">
            <div class="rts-header__wrapper">
                <!-- FOR LOGO -->
                <div class="rts-header__logo">
                    <a href="{{ route('home') }}" class="site-logo">
                        <img class="logo-white" src="{{ asset('logo.webp') }}" alt="{{ config('app.name') }}">
                        <img class="logo-dark" src="{{ asset('logo.webp') }}" alt="{{ config('app.name') }}">
                    </a>
                </div>
                <!-- FOR NAVIGATION MENU -->

                <nav class="rts-header__menu" id="mobile-menu">
                    <div class="hostie-menu">
                        <ul class="list-unstyled hostie-desktop-menu">
                            <li class="menu-item hostie">
                                <a href="{{ route('home') }}" class="hostie-dropdown-main-element">Home</a>
                            </li>

                            <li class="menu-item hostie-has-dropdown mega-menu">
                                <a href="#" class="hostie-dropdown-main-element">Hosting</a>
                                <div class="rts-mega-menu">
                                    <div class="wrapper">
                                        <div class="row g-0">
                                            <div class="col-lg-12">
                                                <ul class="mega-menu-item">
                                                    <li>
                                                        <a href="{{--{{ route('shared.index') }}--}}">
                                                            <img src="{{ asset('assets/images/mega-menu/22.svg') }}"
                                                                 alt="icon">
                                                            <div class="info">
                                                                <p>Shared Hosting</p>
                                                                <span>Manage Shared Hosting</span>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#">
                                                            <img src="{{ asset('assets/images/mega-menu/27.svg') }}"
                                                                 alt="icon">
                                                            <div class="info">
                                                                <p>VPS - High Storage</p>
                                                                <span>Get your highest storage VPS</span>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#">
                                                            <img src="{{ asset('assets/images/mega-menu/24.svg') }}"
                                                                 alt="icon">
                                                            <div class="info">
                                                                <p>VPS High Performance</p>
                                                                <span>Dedicated resources</span>
                                                            </div>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="menu-item hostie-has-dropdown">
                                <a href="#" class="hostie-dropdown-main-element">Domain</a>
                                <ul class="hostie-submenu list-unstyled menu-pages">
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('domains') }}">Register Domain</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{--{{route('admin.domains.transfer.check')}}--}}">Transfer
                                            Domain
                                        </a>
                                    </li>

                                </ul>
                            </li>
                            <li class="menu-item hostie-has-dropdown">
                                <a href="#" class="hostie-dropdown-main-element">Services</a>
                                <ul class="hostie-submenu list-unstyled menu-pages">
                                    <li class="nav-item"><a class="nav-link" href="#">Web Application</a>
                                    </li>
                                    <li class="nav-item"><a class="nav-link" href="#">Mobile
                                            Development</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#">Mobile data
                                            Collection</a>
                                    </li>
                                    <li class="nav-item"><a class="nav-link" href="#">IT Consultancy</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item hostie-has-dropdown">
                                <a href="#" class="hostie-dropdown-main-element">Help Center</a>
                                <ul class="hostie-submenu list-unstyled menu-pages">
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">FAQ</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">Support</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">Contact</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">Knowledgebase</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- FOR HEADER RIGHT -->
                <div class="rts-header__right">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{--{{ url('/dashboard') }}--}}" class="login__btn">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="login__btn">Login</a>
                        @endauth
                    @endif
                    <button id="menu-btn" aria-label="Menu" class="mobile__active menu-btn"><i
                            class="fa-sharp fa-solid fa-bars"></i></button>

                </div>
            </div>
        </div>
    </div>
</header>
<!-- HEADER AREA END -->
