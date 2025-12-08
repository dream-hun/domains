<!-- HEADER AREA -->
<header class="rts-header style-one header__default" style="background-color: #0458d6;">
    <!-- HEADER TOP AREA -->
    <div class="rts-ht rts-ht__bg" style="background-color: #4291fc;">
        <div class="container">
            <div class="row">
                <div class="rts-ht__wrapper">
                    @php($supportEmail = data_get($settings, 'email'))
                    @if ($supportEmail)
                        <div class="rts-ht__email">
                            <a href="mailto:{{ $supportEmail }}">
                                <img src="assets/images/icon/email.svg" alt="" class="icon">{{ $supportEmail }}
                            </a>
                        </div>
                    @endif
                    <div class="rts-ht__links">
                        <div class="live-chat-has-dropdown">
                            <a href="#" class="live__chat">
                                <img src="assets/images/icon/forum.svg" alt="" class="icon">Live Chat
                            </a>
                        </div>
                        <div class="live-chat-has-dropdown">
                            <a href="#" class="live__chat">
                                <livewire:currency-switcher />
                            </a>
                        </div>


                        <div class="live-chat-has-dropdown">
                            <livewire:cart-total />
                        </div>
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
                                                    @foreach ($hostingCategories as $category)
                                                        <li>
                                                            <a href="{{ route('hosting.categories.show', $category->slug) }}">
                                                                @if($category->slug == 'shared-hosting')
                                                                    <img src="{{ asset('assets/images/mega-menu/22.svg') }}" alt="icon">
                                                                @elseif($category->slug == 'wordpress-hosting')
                                                                    <img src="{{ asset('assets/images/mega-menu/27.svg') }}" alt="icon">
                                                                @elseif($category->slug == 'vps-hosting')
                                                                    <img src="{{ asset('assets/images/mega-menu/24.svg') }}" alt="icon">
                                                                @elseif($category->slug == 'reseller-hosting')
                                                                    <img src="{{ asset('assets/images/mega-menu/25.svg') }}" alt="icon">
                                                                @elseif($category->slug == 'dedicated-hosting')
                                                                    <img src="{{ asset('assets/images/mega-menu/26.svg') }}" alt="icon">
                                                                @elseif($category->slug == 'cloud-hosting')
                                                                    <img src="{{ asset('assets/images/mega-menu/28.svg') }}" alt="icon">
                                                                @else
                                                                    <img src="{{ asset('assets/images/mega-menu/22.svg') }}" alt="icon">
                                                                @endif

                                                                <div class="info">
                                                                    <p>{{ $category->name }}</p>
                                                                </div>
                                                            </a>
                                                        </li>
                                                    @endforeach
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
                                        <a class="nav-link" href="{{-- {{route('admin.domains.transfer.check')}} --}}">Transfer
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
                            <a href="{{ url('/dashboard') }}" class="login__btn">Dashboard</a>
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
