<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Your reliable and Secure web hosting service provider">
    <meta name="keywords" content="Hosting, Domain, Transfer, Buy Domain">
    <link rel="canonical" href="https://bluhub.rw">
    <meta name="robots" content="index, follow">
    <!-- for open graph social media -->
    <meta property="og:title" content="{{ config('app.name') }} - Your reliable and Secure hosting service provider">
    <meta property="og:description" content="Your reliable and Secure hosting service provider">
    <meta property="og:image" content="{{ asset('assets/images/banner/slider-img-01.webp') }}">
    <meta property="og:url" content="https:://bluhub.rw">
    <!-- for twitter sharing -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name') }} - Your reliable and Secure hosting service provider">
    <meta name="twitter:description" content="Your reliable and Secure hosting service provider">
    <meta name="twitter:image" content="{{ asset('assets/images/banner/slider-img-01.webp') }}">
    <!-- favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/fav.png">

    <title>@yield('page-title') - {{config('app.name')}}</title>

    <!-- Importing Google Fonts -->
    <link href="{{ asset('font/web/inter.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{asset('font/bootstrap-icons.min.css')}}">
    <!-- all styles -->
    <link rel="preload stylesheet" href="{{ asset('assets/css/plugins.min.css') }}" as="style">
    <!-- fontawesome css -->
    <link rel="preload stylesheet" href="{{ asset('assets/css/plugins/fontawesome.min.css') }}" as="style">
    <!-- Custom css -->
    <link rel="preload stylesheet" href="{{ asset('assets/css/style.css') }}" as="style">
    @livewireStyles
    @stack('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        body{
            font-family: 'Inter', sans-serif !important;
        }
        h1,h2,h3,h4,h5,h6{
            font-family: 'Inter', sans-serif !important;
        }
    </style>
</head>

<body class="loaded domain-page">
<x-menu-component/>

{{ $slot }}
<x-footer-component/>
<div id="anywhere-home" class="">
</div>
<x-sidebar-menu/>
<div class="loader-wrapper">
    <div class="loader">
    </div>
    <div class="loader-section section-left"></div>
    <div class="loader-section section-right"></div>
</div>
<div class="progress-wrap" style="z-index: 1000;">
    <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
        <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"
              style="transition: stroke-dashoffset 10ms linear 0s; stroke-dasharray: 307.919, 307.919; stroke-dashoffset: 307.919; height: 20px; width: auto;">
        </path>
    </svg>
</div>
<!-- BACK TO TOP AREA EDN -->

<!-- All Plugin -->
<script defer src="{{ asset('assets/js/plugins.min.js') }}"></script>
<!-- main js -->
<script defer src="{{ asset('assets/js/main.js') }}"></script>

@livewireScripts
@yield('scripts')
</body>

</html>
