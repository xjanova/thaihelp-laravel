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
    @php $gmapsKey = \App\Models\SiteSetting::get('google_maps_api_key') ?: config('services.google_maps.api_key', ''); @endphp
    @if($gmapsKey)
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $gmapsKey }}&libraries=places&language=th" defer></script>
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

    {{-- Version --}}
    @php
        $version = 'dev';
        $versionFile = base_path('version.txt');
        if (file_exists($versionFile)) {
            $version = trim(file_get_contents($versionFile));
        }
    @endphp
    <div class="fixed bottom-16 right-2 z-40 text-[9px] text-slate-600 opacity-50">{{ $version }}</div>

    {{-- Vite JS --}}
    @vite(['resources/js/app.js'])

    {{-- Livewire --}}
    @livewireScripts

    {{-- Page Scripts --}}
    @stack('scripts')
</body>
</html>
