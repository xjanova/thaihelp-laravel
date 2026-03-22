<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ThaiHelp - ช่วยเหลือคนไทย' }}</title>

    {{-- Google Fonts: Noto Sans Thai --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    {{-- Vite CSS --}}
    @vite(['resources/css/app.css'])

    {{-- Google Maps JS API --}}
    @if(config('services.google.maps_api_key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=places&language=th" defer></script>
    @endif

    @stack('styles')
</head>
<body class="antialiased font-thai">
    {{-- Header --}}
    @include('components.header')

    {{-- Main Content --}}
    <main class="pt-14 pb-16">
        @yield('content')
    </main>

    {{-- Bottom Navigation --}}
    @include('components.bottom-nav')

    {{-- Vite JS --}}
    @vite(['resources/js/app.js'])

    {{-- Livewire --}}
    @livewireScripts

    {{-- Page Scripts --}}
    @stack('scripts')
</body>
</html>
