<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mospanle-web-app-capable" content="yes">
    <meta name="apple-mospanle-web-app-capable" content="yes">
    <meta name="description" content="Your Ultimate Solution for Web Hosting & WHMCS">
    <meta name="keywords" content="Hosting, Domain, Transfer, Buy Domain, WHMCS">
    <link rel="canonical" href="https://bluhub.rw">
    <meta name="robots" content="index, follow">
    <!-- for open graph social media -->
    <meta property="og:title" content="Bluhub - Web Hosting & Domain registration">
    <meta property="og:description" content="Your Ultimate Solution for Web Hosting & WHMCS">
    <meta property="og:image" content="https://bluhub.rwimage.jpg">
    <meta property="og:url" content="https://bluhub.rw">
    <!-- for twitter sharing -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Bluhub - Web Hosting & WHMCS Template">
    <meta name="twitter:description" content="Your Ultimate Solution for Web Hosting & WHMCS">
    <meta name="twitter:image" content="https://bluhub.rw/assets/images/banner/slider-img-01.webp">
    <!-- favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/fav.png">
    <title>@yield('page-title') - {{config('app.name')}}</title>
    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('font/web/inter.css') }}">
    <link rel="stylesheet" href="{{asset('font/bootstrap-icons.min.css')}}">
    <!-- all styles -->
    <link rel="preload stylesheet" href="{{ asset('assets/css/plugins.min.css') }}" as="style">
    <!-- fontawesome css -->
    <link rel="preload stylesheet" href="{{ asset('assets/css/plugins/fontawesome.min.css') }}" as="style">
    <!-- Custom css -->
    <link rel="preload stylesheet" href="{{ asset('assets/css/style.css') }}" as="style">

    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif !important;
        }
        h1,h2,h3,h4,h5,h6 {
            font-family: 'Inter', sans-serif !important;
        }
    </style>
    @stack('styles')
</head>

<body class="loaded reset-password-page">
<x-guest-menu-component/>

{{ $slot }}

<x-footer-component></x-footer-component>
@stack('scripts')

</body>

</html>
