@extends('layouts.app')

@section('content')
<div class="relative" style="height: calc(100vh - 7.5rem);">
    {{-- Google Map --}}
    <div id="map" class="w-full h-full"></div>

    {{-- Map Filter Buttons --}}
    <div class="absolute top-3 left-3 flex gap-2 z-10">
        <button onclick="setFilter('all')" id="filter-all" class="metal-btn-accent px-3 py-1.5 rounded-full text-xs font-medium text-white">
            ทั้งหมด
        </button>
        <button onclick="setFilter('stations')" id="filter-stations" class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300">
            ปั๊ม
        </button>
        <button onclick="setFilter('incidents')" id="filter-incidents" class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300">
            เหตุการณ์
        </button>
    </div>

    {{-- Welcome Overlay --}}
    <div id="welcome-overlay" x-data="{ show: !localStorage.getItem('thaihelp_welcomed') }" x-show="show" x-transition
         class="absolute inset-0 z-20 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="metal-panel rounded-2xl p-6 mx-4 max-w-sm w-full text-center">
            {{-- Logo --}}
            <div class="flex items-center justify-center gap-2 mb-4">
                <img src="/images/logo.png" alt="ThaiHelp" class="w-12 h-12 rounded-xl" onerror="this.style.display='none'">
                <h1 class="text-2xl font-bold">
                    <span class="text-blue-500">Thai</span><span class="text-orange-500">Help</span>
                </h1>
            </div>

            {{-- Avatar Greeting --}}
            <div class="mb-4">
                <div class="w-20 h-20 mx-auto mb-3 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-3xl">
                    👧
                </div>
                <p class="text-slate-300 text-sm">สวัสดีค่ะ! น้องหญิงยินดีต้อนรับสู่ ThaiHelp</p>
                <p class="text-slate-400 text-xs mt-1">ผู้ช่วยอัจฉริยะสำหรับคนไทย</p>
            </div>

            {{-- Feature Cards --}}
            <div class="grid grid-cols-2 gap-2 mb-5">
                <a href="/stations" class="metal-panel metal-panel-hover rounded-xl p-3 text-center">
                    <div class="text-2xl mb-1">⛽</div>
                    <div class="text-xs font-medium text-slate-300">ปั๊มน้ำมัน</div>
                    <div class="text-[10px] text-slate-500">ค้นหาใกล้คุณ</div>
                </a>
                <a href="/report" class="metal-panel metal-panel-hover rounded-xl p-3 text-center">
                    <div class="text-2xl mb-1">🚨</div>
                    <div class="text-xs font-medium text-slate-300">แจ้งเหตุ</div>
                    <div class="text-[10px] text-slate-500">รายงานปัญหา</div>
                </a>
                <a href="/chat" class="metal-panel metal-panel-hover rounded-xl p-3 text-center">
                    <div class="text-2xl mb-1">🤖</div>
                    <div class="text-xs font-medium text-slate-300">AI Chat</div>
                    <div class="text-[10px] text-slate-500">ถามน้องหญิง</div>
                </a>
                <div class="metal-panel rounded-xl p-3 text-center cursor-pointer" onclick="closeWelcome()">
                    <div class="text-2xl mb-1">🗺️</div>
                    <div class="text-xs font-medium text-slate-300">ดูแผนที่</div>
                    <div class="text-[10px] text-slate-500">สำรวจพื้นที่</div>
                </div>
            </div>

            {{-- Close Button --}}
            <button onclick="closeWelcome()" class="metal-btn-accent w-full py-2.5 rounded-xl text-sm font-semibold text-white">
                ดูแผนที่
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let map;
    let currentFilter = 'all';

    function closeWelcome() {
        const overlay = document.getElementById('welcome-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            localStorage.setItem('thaihelp_welcomed', 'true');
        }
    }

    function setFilter(filter) {
        currentFilter = filter;
        const filters = ['all', 'stations', 'incidents'];
        filters.forEach(f => {
            const btn = document.getElementById('filter-' + f);
            if (f === filter) {
                btn.className = 'metal-btn-accent px-3 py-1.5 rounded-full text-xs font-medium text-white';
            } else {
                btn.className = 'metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300';
            }
        });
        // TODO: Filter map markers based on selection
    }

    function initMap() {
        const defaultCenter = { lat: 13.7563, lng: 100.5018 }; // Bangkok

        map = new google.maps.Map(document.getElementById('map'), {
            center: defaultCenter,
            zoom: 13,
            styles: [
                { elementType: 'geometry', stylers: [{ color: '#0a0e17' }] },
                { elementType: 'labels.text.stroke', stylers: [{ color: '#0a0e17' }] },
                { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
                { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#334155' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
                { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#111827' }] },
                { featureType: 'transit', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
            ],
            disableDefaultUI: true,
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
        });

        // Try to get user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    map.setCenter(pos);
                    new google.maps.Marker({
                        position: pos,
                        map: map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: '#3b82f6',
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 2,
                        },
                        title: 'ตำแหน่งของคุณ',
                    });
                },
                () => {
                    console.log('Geolocation permission denied, using default location');
                }
            );
        }
    }

    // Initialize map when Google Maps API is loaded
    if (typeof google !== 'undefined' && google.maps) {
        initMap();
    } else {
        window.addEventListener('load', () => {
            if (typeof google !== 'undefined' && google.maps) {
                initMap();
            }
        });
    }
</script>
@endpush
