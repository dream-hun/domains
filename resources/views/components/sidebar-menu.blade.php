<!-- side bar area  -->
<div id="side-bar" class="side-bar header-two">
    <button class="close-icon-menu" aria-label="Close Menu"><i class="fa-sharp fa-thin fa-xmark"></i></button>
    <!-- mobile menu area start -->
    <div class="mobile-menu-main">
        <nav class="nav-main mainmenu-nav mt--30">
            <ul class="mainmenu metismenu" id="mobile-menu-active">
                <li class="mobile-menu-link">
                    <a href="{{route('home')}}" class="main">Home</a>
                </li>

                <li class="has-droupdown">
                    <a href="#" class="main">Hosting</a>
                    <ul class="submenu mm-collapse">
                        @foreach ($hostings as $hosting)
                            <li><a class="mobile-menu-link" href="{{ route('hosting.categories.show', $hosting->slug) }}">{{ $hosting->name }}</a></li>
                        @endforeach
                    </ul>
                </li>
                <li class=" has-droupdown">
                    <a href="#" class="main">Domain</a>
                    <ul class="submenu mm-collapse">
                        <li><a class="mobile-menu-link" href="{{ route('domains')}}">Register
                                Domain</a></li>
                        {{-- <li><a class="mobile-menu-link"
                               href="#">Transfer Domain</a></li> --}}
                    </ul>
                </li>
                <li class="has-droupdown">
                    <a href="#" class="main">Services</a>
                    <ul class="submenu mm-collapse">
                        <li><a class="mobile-menu-link" href="#">Web Application</a></li>
                        <li><a class="mobile-menu-link" href="#">Mobile Development</a></li>
                        <li><a class="mobile-menu-link" href="#">Mobile Data Collection</a></li>
                        <li><a class="mobile-menu-link" href="#">IT Consultancy</a></li>
                    </ul>
                </li>
                <li class="has-droupdown">
                    <a href="#" class="main">Help Center</a>
                    <ul class="submenu mm-collapse">
                        <li><a class="mobile-menu-link" href="#">FAQ</a></li>
                        <li><a class="mobile-menu-link" href="#">Support</a></li>
                        <li><a class="mobile-menu-link" href="#">Whois</a></li>
                        <li><a class="mobile-menu-link" href="#">Contact</a></li>
                        <li><a class="mobile-menu-link" href="#">Knowledge base</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <ul class="social-area-one pl--20 mt--100">
            <li>
                <a href="https://www.linkedin.com" aria-label="social-link" target="_blank">
                    <i class="fa-brands fa-linkedin"></i>
                </a>
            </li>
            <li>
                <a href="https://www.x.com" aria-label="social-link" target="_blank">
                    <i class="fa-brands fa-twitter"></i>
                </a>
            </li>
            <li>
                <a href="https://www.youtube.com" aria-label="social-link" target="_blank">
                    <i class="fa-brands fa-youtube"></i>
                </a>
            </li>
            <li>
                <a href="https://www.facebook.com" aria-label="social-link" target="_blank">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
            </li>
        </ul>
    </div>

</div>
