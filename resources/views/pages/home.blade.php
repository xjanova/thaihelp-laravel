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

    {{-- Map Legend --}}
    <div class="absolute bottom-3 left-3 z-10 metal-panel rounded-lg px-3 py-2 text-xs space-y-1">
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> มีน้ำมัน</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span> เหลือน้อย</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> หมด</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-gray-500 inline-block"></span> ไม่มีข้อมูล</div>
    </div>

    {{-- News Ticker Panel --}}
    <div id="news-panel" x-data="newsPanel()" x-show="show" x-transition
         class="absolute top-14 right-3 z-10 w-80 max-h-[60vh] metal-panel rounded-xl overflow-hidden shadow-2xl border border-slate-700/50">
        {{-- Header --}}
        <div class="flex items-center justify-between px-3 py-2 bg-gradient-to-r from-orange-600/80 to-red-600/80 backdrop-blur-sm">
            <div class="flex items-center gap-2">
                <span class="text-sm">📰</span>
                <span class="text-xs font-bold text-white">ข่าวพลังงาน</span>
                <span class="bg-white/20 px-1.5 py-0.5 rounded text-[10px] text-white" x-text="newsCount + ' ข่าว'"></span>
            </div>
            <div class="flex items-center gap-1">
                <button @click="expanded = !expanded" class="text-white/70 hover:text-white p-0.5">
                    <svg x-show="!expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    <svg x-show="expanded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </button>
                <button @click="show = false" class="text-white/70 hover:text-white p-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- News List --}}
        <div x-show="expanded" x-transition class="overflow-y-auto max-h-[50vh] divide-y divide-slate-700/30">
            <template x-for="(item, idx) in news" :key="idx">
                <a :href="item.source_url" target="_blank" rel="noopener"
                   class="block px-3 py-2.5 hover:bg-slate-700/30 transition-colors group">
                    <div class="flex items-start gap-2">
                        <span class="text-sm mt-0.5" x-text="item.category === 'crisis' ? '🔴' : item.category === 'fuel' ? '⛽' : '📰'"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-slate-200 group-hover:text-orange-400 transition-colors line-clamp-2" x-text="item.title"></p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] text-slate-500" x-text="item.source_name"></span>
                                <span class="text-[10px] text-slate-600">•</span>
                                <span class="text-[10px] text-slate-500" x-text="timeAgo(item.published_at)"></span>
                            </div>
                        </div>
                    </div>
                </a>
            </template>

            <div x-show="news.length === 0" class="px-3 py-4 text-center">
                <span class="text-xs text-slate-500">กำลังโหลดข่าว...</span>
            </div>
        </div>

        {{-- Footer --}}
        <div x-show="expanded" class="px-3 py-1.5 bg-slate-800/50 text-center">
            <span class="text-[10px] text-slate-500">อัพเดททุก 5 ชม. • ลบอัตโนมัติทุกวัน</span>
        </div>
    </div>

    {{-- News Reopen Button (when closed) --}}
    <button id="news-reopen" x-data="{ hidden: false }" x-show="hidden"
            @click="hidden = false; document.getElementById('news-panel').__x.$data.show = true"
            class="absolute top-14 right-3 z-10 metal-btn p-2 rounded-full shadow-lg">
        <span class="text-sm">📰</span>
    </button>

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
    let stationMarkers = [];
    let incidentMarkers = [];
    let userPos = { lat: 13.7563, lng: 100.5018 };

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
        // Apply filter to markers
        stationMarkers.forEach(m => m.setVisible(filter === 'all' || filter === 'stations'));
        incidentMarkers.forEach(m => m.setVisible(filter === 'all' || filter === 'incidents'));
    }

    async function loadMapData() {
        try {
            // Load stations
            const stationsRes = await fetch(`/api/stations?lat=${userPos.lat}&lng=${userPos.lng}&radius=10000`);
            const stationsData = await stationsRes.json();
            const stations = stationsData.success ? (stationsData.data || []) : (stationsData.data || []);
            stations.forEach(station => {
                const lat = station.latitude || station.lat;
                const lng = station.longitude || station.lng;
                if (!lat || !lng) return;

                // Determine color from fuel reports
                let color = '#6b7280'; // gray = no data
                const fuels = station.fuel_reports || [];
                if (fuels.length > 0) {
                    const hasEmpty = fuels.some(f => f.status === 'empty');
                    const hasLow = fuels.some(f => f.status === 'low');
                    const hasAvailable = fuels.some(f => f.status === 'available');
                    if (hasEmpty && !hasAvailable) color = '#ef4444'; // red
                    else if (hasLow) color = '#eab308'; // yellow
                    else if (hasAvailable) color = '#22c55e'; // green
                }

                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(lat), lng: parseFloat(lng) },
                    map: map,
                    title: station.name || 'Gas Station',
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8,
                        fillColor: color,
                        fillOpacity: 0.9,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                    },
                });

                // Rich info window with fuel status
                let fuelHtml = '';
                if (fuels.length > 0) {
                    fuelHtml = '<div style="margin-top:6px">';
                    fuels.forEach(f => {
                        const emoji = f.status === 'available' ? '🟢' : f.status === 'low' ? '🟡' : '🔴';
                        const price = f.price ? ` ฿${f.price}` : '';
                        fuelHtml += `<div>${emoji} ${f.fuel_type}${price}</div>`;
                    });
                    fuelHtml += '</div>';
                }

                const infoWindow = new google.maps.InfoWindow({
                    content: `<div style="color:#000;font-size:13px;min-width:150px">
                        <strong>${station.name || 'ปั๊มน้ำมัน'}</strong>
                        <div style="color:#666;font-size:11px">${station.brand || ''} ${station.vicinity || ''}</div>
                        ${fuelHtml}
                        ${station.last_report_at ? '<div style="color:#999;font-size:10px;margin-top:4px">อัพเดท: ' + new Date(station.last_report_at).toLocaleString('th-TH') + '</div>' : ''}
                    </div>`
                });
                marker.addListener('click', () => infoWindow.open(map, marker));
                stationMarkers.push(marker);
            });
        } catch (err) {
            console.error('Failed to load stations:', err);
        }

        try {
            // Load incidents
            const incidentsRes = await fetch(`/api/incidents?lat=${userPos.lat}&lng=${userPos.lng}&radius=10000`);
            const incidentsData = await incidentsRes.json();
            const incidents = incidentsData.success ? (incidentsData.data || []) : (incidentsData.data || []);
            incidents.forEach(incident => {
                const lat = incident.latitude || incident.lat;
                const lng = incident.longitude || incident.lng;
                if (!lat || !lng) return;
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(lat), lng: parseFloat(lng) },
                    map: map,
                    title: incident.title || 'Incident',
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 7,
                        fillColor: '#ef4444',
                        fillOpacity: 0.9,
                        strokeColor: '#ffffff',
                        strokeWeight: 1.5,
                    },
                });
                const infoWindow = new google.maps.InfoWindow({
                    content: `<div style="color:#000;font-size:13px"><strong>${incident.title || 'เหตุการณ์'}</strong><br>${incident.category || ''}</div>`
                });
                marker.addListener('click', () => infoWindow.open(map, marker));
                incidentMarkers.push(marker);
            });
        } catch (err) {
            console.error('Failed to load incidents:', err);
        }
    }

    // Radar pulse animation overlay
    function addRadarPulse(center) {
        const radarDiv = document.createElement('div');
        radarDiv.innerHTML = `
            <div class="radar-container">
                <div class="radar-ring radar-ring-1"></div>
                <div class="radar-ring radar-ring-2"></div>
                <div class="radar-ring radar-ring-3"></div>
            </div>
        `;
        document.getElementById('map').parentElement.appendChild(radarDiv);

        // CSS for radar
        if (!document.getElementById('radar-style')) {
            const style = document.createElement('style');
            style.id = 'radar-style';
            style.textContent = `
                .radar-container { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 5; pointer-events: none; }
                .radar-ring {
                    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                    border: 2px solid rgba(59, 130, 246, 0.5); border-radius: 50%;
                    animation: radarPulse 3s ease-out infinite;
                }
                .radar-ring-1 { width: 60px; height: 60px; animation-delay: 0s; }
                .radar-ring-2 { width: 60px; height: 60px; animation-delay: 1s; }
                .radar-ring-3 { width: 60px; height: 60px; animation-delay: 2s; }
                @keyframes radarPulse {
                    0% { width: 20px; height: 20px; opacity: 1; border-color: rgba(59, 130, 246, 0.8); }
                    100% { width: 200px; height: 200px; opacity: 0; border-color: rgba(59, 130, 246, 0); }
                }
            `;
            document.head.appendChild(style);
        }

        // Remove radar after 10 seconds
        setTimeout(() => radarDiv.remove(), 10000);
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
                    userPos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    map.setCenter(userPos);
                    // User location marker with pulse
                    new google.maps.Marker({
                        position: userPos,
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

                    // Radar pulse animation
                    addRadarPulse(userPos);

                    loadMapData();
                },
                () => {
                    console.log('Geolocation permission denied, using default location');
                    loadMapData();
                }
            );
        } else {
            loadMapData();
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

    // News Panel Alpine component
    function newsPanel() {
        return {
            show: true,
            expanded: true,
            news: [],
            newsCount: 0,
            init() {
                this.loadNews();
                // Refresh every 30 min
                setInterval(() => this.loadNews(), 30 * 60 * 1000);
            },
            async loadNews() {
                try {
                    const res = await fetch('/api/news');
                    const data = await res.json();
                    if (data.success) {
                        this.news = data.data || [];
                        this.newsCount = this.news.length;
                    }
                } catch (e) {
                    console.error('Failed to load news:', e);
                }
            },
            timeAgo(dateStr) {
                if (!dateStr) return '';
                const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
                if (diff < 60) return 'เมื่อสักครู่';
                if (diff < 3600) return Math.floor(diff / 60) + ' นาทีที่แล้ว';
                if (diff < 86400) return Math.floor(diff / 3600) + ' ชม.ที่แล้ว';
                return Math.floor(diff / 86400) + ' วันที่แล้ว';
            },
        };
    }
</script>
@endpush
