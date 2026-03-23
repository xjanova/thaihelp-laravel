@extends('layouts.app')

@section('content')
<div class="relative" style="height: calc(100vh - 7.5rem);">
    {{-- Google Map --}}
    <div id="map" class="w-full h-full"></div>

    {{-- Map Controls --}}
    <div class="absolute top-3 left-3 z-10 space-y-2">
        {{-- Filter Buttons --}}
        <div class="flex gap-2">
            <button onclick="setFilter('all')" id="filter-all" class="metal-btn-accent px-3 py-1.5 rounded-full text-xs font-medium text-white">
                ทั้งหมด
            </button>
            <button onclick="setFilter('stations')" id="filter-stations" class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300">
                ⛽ ปั๊ม
            </button>
            <button onclick="setFilter('incidents')" id="filter-incidents" class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300">
                🚨 เหตุการณ์
            </button>
        </div>

        {{-- Display Options --}}
        <div class="flex gap-2">
            <button onclick="toggleBalloons()" id="btn-balloons" class="metal-btn px-2.5 py-1 rounded-full text-[10px] text-slate-400">
                🎈 บอลลูน
            </button>
            <button onclick="toggleLabels()" id="btn-labels" class="metal-btn-accent px-2.5 py-1 rounded-full text-[10px] text-white">
                📌 ป้าย
            </button>
            <button onclick="togglePOI()" id="btn-poi" class="metal-btn px-2.5 py-1 rounded-full text-[10px] text-slate-400">
                🏫 สถานที่
            </button>
        </div>
    </div>

    {{-- Breaking News Banner --}}
    <div id="breaking-news-bar" style="display:none;"
         class="absolute top-[4.5rem] left-3 right-3 z-10 bg-red-600/90 backdrop-blur-sm rounded-xl px-3 py-2 shadow-xl cursor-pointer"
         onclick="toggleBreakingNews()">
        <div class="flex items-center gap-2">
            <span class="text-sm animate-pulse">🔴</span>
            <span id="breaking-news-title" class="text-xs font-medium text-white flex-1 truncate"></span>
            <span class="text-[10px] text-white/60" id="breaking-news-count"></span>
        </div>
    </div>

    {{-- Breaking News Detail Panel --}}
    <div id="breaking-news-panel" style="display:none;"
         class="absolute top-[7rem] left-3 right-3 z-10 metal-panel rounded-xl max-h-[50vh] overflow-y-auto shadow-2xl border border-red-500/30">
        <div class="p-3 border-b border-slate-700 flex items-center justify-between">
            <span class="text-sm font-bold text-red-400">🔴 ข่าวด่วน</span>
            <button onclick="document.getElementById('breaking-news-panel').style.display='none'" class="text-slate-500 hover:text-white">&times;</button>
        </div>
        <div id="breaking-news-list" class="divide-y divide-slate-700/30"></div>
    </div>

    {{-- Data Layers Panel (เปิดปิดได้) --}}
    <div id="data-layers-panel" class="absolute top-3 right-3 z-10" x-data="{ open: false }">
        <button @click="open = !open" class="metal-btn-accent px-3 py-1.5 rounded-full text-xs font-medium text-white shadow-lg">
            📊 ข้อมูล <span x-text="open ? '▲' : '▼'" class="ml-1 text-[10px]"></span>
        </button>
        <div x-show="open" x-transition class="mt-2 metal-panel rounded-xl p-3 w-56 shadow-2xl space-y-2">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider">เลเยอร์ข้อมูล</p>
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" id="layer-weather" checked onchange="toggleLayer('weather')" class="rounded accent-blue-500">
                🌤️ สภาพอากาศ
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" id="layer-aqi" checked onchange="toggleLayer('aqi')" class="rounded accent-green-500">
                💨 คุณภาพอากาศ
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" id="layer-earthquake" checked onchange="toggleLayer('earthquake')" class="rounded accent-orange-500">
                🫨 แผ่นดินไหว
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" id="layer-flood" onchange="toggleLayer('flood')" class="rounded accent-cyan-500">
                🌊 เตือนน้ำท่วม
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" id="layer-traffic" checked onchange="toggleLayer('traffic')" class="rounded accent-red-500">
                🚗 จราจร
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" id="layer-danger" checked onchange="toggleLayer('danger')" class="rounded accent-red-600">
                🔴 พื้นที่อันตราย
            </label>
            <hr class="border-slate-700">
            <button onclick="refreshExternalData()" class="w-full text-center text-[10px] text-blue-400 hover:text-blue-300">🔄 รีเฟรชข้อมูล</button>
        </div>
    </div>

    {{-- Weather + AQI Widget (ด้านขวาล่าง) --}}
    <div id="weather-widget" class="absolute bottom-3 right-3 z-10 metal-panel rounded-xl px-3 py-2 text-xs max-w-[180px] shadow-lg">
        <div id="weather-content" class="space-y-1">
            <div class="text-slate-500 text-[10px]">กำลังโหลด...</div>
        </div>
    </div>

    {{-- Map Legend --}}
    <div class="absolute bottom-3 left-3 z-10 metal-panel rounded-lg px-3 py-2 text-xs space-y-1">
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> มีน้ำมัน</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span> เหลือน้อย</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> หมด</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-gray-500 inline-block"></span> ไม่มีข้อมูล</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-600 inline-block animate-pulse"></span> ข่าวด่วน</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span> แผ่นดินไหว</div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 border-2 border-red-500 rounded-full inline-block"></span> พื้นที่อันตราย</div>
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
                <img src="/images/logo.webp" alt="ThaiHelp" class="w-12 h-12 rounded-xl" onerror="this.style.display='none'">
                <h1 class="text-2xl font-bold">
                    <span class="text-blue-500">Thai</span><span class="text-orange-500">Help</span>
                </h1>
            </div>

            {{-- Avatar Greeting --}}
            <div class="mb-4">
                <div class="w-20 h-20 mx-auto mb-3 rounded-full overflow-hidden ring-4 ring-orange-500/50 shadow-lg shadow-orange-500/20">
                    <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-3xl\'>👧</div>'">
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
    let showPOI = false;

    // ─── Map Styles ───
    const mapStyleBase = [
        { elementType: 'geometry', stylers: [{ color: '#0a0e17' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#0a0e17' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
        { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#334155' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
        { featureType: 'transit', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
    ];

    // Clean: hide ALL POIs (schools, hotels, shops, parks, etc.)
    const mapStylesClean = [
        ...mapStyleBase,
        { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.business', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.government', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.school', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.medical', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.place_of_worship', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.sports_complex', stylers: [{ visibility: 'off' }] },
        { featureType: 'transit', stylers: [{ visibility: 'off' }] },
    ];

    // With POI: show everything
    const mapStylesWithPOI = [
        ...mapStyleBase,
        { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#111827' }] },
        { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#64748b' }] },
    ];

    function togglePOI() {
        showPOI = !showPOI;
        map.setOptions({ styles: showPOI ? mapStylesWithPOI : mapStylesClean });
        const btn = document.getElementById('btn-poi');
        btn.className = showPOI
            ? 'metal-btn-accent px-2.5 py-1 rounded-full text-[10px] text-white'
            : 'metal-btn px-2.5 py-1 rounded-full text-[10px] text-slate-400';
    }

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
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                    setTimeout(() => infoWindow.close(), 7000);
                });
                stationMarkers.push(marker);
            });
        } catch (err) {
            console.error('Failed to load stations:', err);
        }

        try {
            // Load incidents (radius-filtered)
            const mapRadius = getMapRadius();
            const incidentsRes = await fetch(`/api/incidents?lat=${userPos.lat}&lng=${userPos.lng}&radius=${mapRadius}`);
            const incidentsData = await incidentsRes.json();
            const incidents = incidentsData.success ? (incidentsData.data || []) : (incidentsData.data || []);

            const severityColors = { critical: '#dc2626', high: '#f97316', medium: '#eab308', low: '#22c55e' };
            const severityLabels = { critical: 'วิกฤต', high: 'รุนแรง', medium: 'ปานกลาง', low: 'เล็กน้อย' };
            const categoryEmoji = { accident:'🚗', flood:'🌊', roadblock:'🚧', checkpoint:'👮', construction:'🏗️', fuel_shortage:'⛽', fire:'🔥', protest:'📢', crime:'🚨', other:'⚠️' };
            const categoryLabels = { accident:'อุบัติเหตุ', flood:'น้ำท่วม', roadblock:'ถนนปิด', checkpoint:'จุดตรวจ', construction:'ก่อสร้าง', fuel_shortage:'น้ำมันหมด', fire:'ไฟไหม้', protest:'ชุมนุม', crime:'อาชญากรรม', other:'อื่นๆ' };
            const statusLabels = { active:'กำลังเกิด', confirmed:'ยืนยันแล้ว', resolved:'คลี่คลาย' };

            incidents.forEach(incident => {
                const lat = incident.latitude || incident.lat;
                const lng = incident.longitude || incident.lng;
                if (!lat || !lng) return;

                const sev = incident.severity || 'medium';
                const sevColor = severityColors[sev] || '#eab308';
                const sevScale = sev === 'critical' ? 12 : sev === 'high' ? 10 : 8;
                const emoji = categoryEmoji[incident.category] || '⚠️';
                const catLabel = categoryLabels[incident.category] || incident.category;
                const statusBadge = incident.status === 'confirmed'
                    ? '<span style="background:#22c55e;color:#fff;padding:1px 5px;border-radius:8px;font-size:9px;">✓ ยืนยันแล้ว</span>'
                    : incident.status === 'resolved'
                    ? '<span style="background:#6b7280;color:#fff;padding:1px 5px;border-radius:8px;font-size:9px;">คลี่คลาย</span>'
                    : '';
                const demoBadge = incident.is_demo
                    ? '<span style="background:#f59e0b;color:#000;padding:1px 5px;border-radius:8px;font-size:9px;">DEMO</span> '
                    : '';

                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(lat), lng: parseFloat(lng) },
                    map: map,
                    title: incident.title || 'เหตุการณ์',
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: sevScale,
                        fillColor: sevColor,
                        fillOpacity: 0.9,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                    },
                    zIndex: sev === 'critical' ? 400 : sev === 'high' ? 300 : 200,
                });

                // 🔴 Danger Zone — วงกลมแดงห้ามเข้า
                if (incident.is_danger_zone || (incident.severity === 'critical' && (incident.confirmation_count || 0) >= 5)) {
                    const dangerRadius = (incident.danger_radius_km || 0.5) * 1000; // km → meters
                    const dangerCircle = new google.maps.Circle({
                        map: map,
                        center: { lat: parseFloat(lat), lng: parseFloat(lng) },
                        radius: dangerRadius,
                        fillColor: '#dc2626',
                        fillOpacity: 0.15,
                        strokeColor: '#dc2626',
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        zIndex: 50,
                        clickable: false,
                    });
                    // Pulse animation
                    let growing = true;
                    setInterval(() => {
                        const current = dangerCircle.get('strokeOpacity');
                        dangerCircle.setOptions({ strokeOpacity: growing ? 1 : 0.4 });
                        growing = !growing;
                    }, 1500);
                    incidentMarkers.push(dangerCircle); // Track for cleanup
                }

                const photos = (incident.photos || []).slice(0, 3).map(url =>
                    `<img src="${url}" style="width:50px;height:50px;object-fit:cover;border-radius:4px;" onerror="this.remove()">`
                ).join('');

                const infoContent = `<div style="color:#000;font-size:12px;min-width:200px;max-width:280px;font-family:sans-serif;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        <span style="font-size:16px">${emoji}</span>
                        <strong style="flex:1;font-size:13px;">${incident.title || 'เหตุการณ์'}</strong>
                    </div>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:4px;">
                        ${demoBadge}
                        <span style="background:${sevColor};color:#fff;padding:1px 5px;border-radius:8px;font-size:9px;">${severityLabels[sev] || sev}</span>
                        <span style="background:#334155;color:#94a3b8;padding:1px 5px;border-radius:8px;font-size:9px;">${catLabel}</span>
                        ${statusBadge}
                    </div>
                    ${incident.description ? '<p style="color:#555;font-size:11px;margin:4px 0;">' + incident.description.substring(0, 120) + '</p>' : ''}
                    ${incident.road_name ? '<p style="color:#888;font-size:10px;">📍 ' + incident.road_name + '</p>' : ''}
                    ${incident.location_name ? '<p style="color:#888;font-size:10px;">📌 ' + incident.location_name + '</p>' : ''}
                    ${incident.has_injuries ? '<p style="color:#dc2626;font-size:10px;font-weight:bold;">🚑 มีผู้บาดเจ็บ</p>' : ''}
                    ${photos ? '<div style="display:flex;gap:3px;margin-top:4px;">' + photos + '</div>' : ''}
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;padding-top:4px;border-top:1px solid #e2e8f0;">
                        <span style="color:#999;font-size:9px;">👥 ${incident.confirmation_count || 0} ยืนยัน · 👍 ${incident.upvotes || 0}</span>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank"
                           style="background:#3b82f6;color:#fff;padding:2px 8px;border-radius:10px;font-size:10px;text-decoration:none;">🧭 นำทาง</a>
                    </div>
                </div>`;

                const infoWindow = new google.maps.InfoWindow({ content: infoContent });
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                    setTimeout(() => infoWindow.close(), 10000);
                });
                incidentMarkers.push(marker);
            });
        } catch (err) {
            console.error('Failed to load incidents:', err);
        }

        // Add balloon labels for stations with fuel
        balloonLabels.forEach(l => l.setMap(null));
        balloonLabels = [];
        stationMarkers.forEach((marker, idx) => {
            const title = marker.getTitle() || '';
            if (title && marker.getVisible()) {
                addBalloonLabel(marker.getPosition(), title, '#22c55e');
            }
        });

        // Load breaking news
        loadBreakingNews();

        // Load external data (weather, AQI, earthquakes, floods)
        loadExternalData();
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
            styles: mapStylesClean,
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

    // ─── External Data Layers ───
    let extMarkers = { earthquake: [], flood: [], traffic: [], danger: [] };
    let extLayers = { weather: true, aqi: true, earthquake: true, flood: false, traffic: true, danger: true };
    let extData = {};

    function toggleLayer(layer) {
        extLayers[layer] = !extLayers[layer];
        if (layer === 'earthquake' || layer === 'flood' || layer === 'traffic' || layer === 'danger') {
            (extMarkers[layer] || []).forEach(m => m.setMap(extLayers[layer] ? map : null));
        }
        if (layer === 'weather' || layer === 'aqi') {
            const widget = document.getElementById('weather-widget');
            if (widget) widget.style.display = (extLayers.weather || extLayers.aqi) ? 'block' : 'none';
            updateWeatherWidget();
        }
    }

    async function loadExternalData() {
        try {
            const res = await fetch(`/api/external-data?lat=${userPos.lat}&lng=${userPos.lng}`);
            const json = await res.json();
            if (!json.success) return;
            extData = json.data;

            renderExternalData();
        } catch (e) {
            console.log('External data failed:', e);
        }
    }

    function refreshExternalData() {
        // Clear old markers
        Object.keys(extMarkers).forEach(key => {
            (extMarkers[key] || []).forEach(m => m.setMap(null));
            extMarkers[key] = [];
        });
        loadExternalData();
    }

    function renderExternalData() {
        // 🫨 Earthquakes
        (extData.earthquakes || []).forEach(eq => {
            if (!eq.latitude || !eq.longitude) return;
            const magScale = Math.max(6, eq.magnitude * 4);
            const marker = new google.maps.Marker({
                position: { lat: eq.latitude, lng: eq.longitude },
                map: extLayers.earthquake ? map : null,
                title: eq.title,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: magScale,
                    fillColor: '#f97316',
                    fillOpacity: 0.7,
                    strokeColor: '#fbbf24',
                    strokeWeight: 2,
                },
                zIndex: 150,
            });
            const iw = new google.maps.InfoWindow({
                content: `<div style="color:#000;font-size:12px;min-width:180px;">
                    <strong>🫨 ${eq.title}</strong>
                    <div style="margin-top:4px;font-size:11px;">
                        <div>ขนาด: <strong style="color:#f97316;">${eq.magnitude}</strong></div>
                        <div>ลึก: ${eq.depth_km} กม.</div>
                        <div>เวลา: ${eq.time || 'ไม่ทราบ'}</div>
                        ${eq.tsunami ? '<div style="color:red;font-weight:bold;">⚠️ เตือนสึนามิ!</div>' : ''}
                    </div>
                    ${eq.url ? '<a href="' + eq.url + '" target="_blank" style="color:#3b82f6;font-size:10px;">ดูรายละเอียด USGS →</a>' : ''}
                </div>`
            });
            marker.addListener('click', () => iw.open(map, marker));
            extMarkers.earthquake.push(marker);
        });

        // 🌤️ Weather + 💨 AQI Widget
        updateWeatherWidget();
    }

    function updateWeatherWidget() {
        const el = document.getElementById('weather-content');
        if (!el) return;

        let html = '';
        const w = extData.weather?.current;
        const aqi = extData.air_quality;

        if (extLayers.weather && w) {
            html += `<div class="flex items-center gap-1">
                <span class="text-lg">${w.icon || '🌤️'}</span>
                <span class="text-white font-bold text-sm">${w.temp}°C</span>
            </div>
            <div class="text-slate-400">${w.description || ''}</div>
            <div class="text-slate-500 text-[10px]">💧 ${w.humidity}% · 💨 ${w.wind_speed} km/h</div>`;
            if (w.rain > 0) {
                html += `<div class="text-cyan-400 text-[10px]">🌧️ ฝน ${w.rain} mm</div>`;
            }
        }

        if (extLayers.aqi && aqi && aqi.aqi) {
            html += `<hr class="border-slate-700 my-1">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full inline-block" style="background:${aqi.color}"></span>
                <span class="text-white text-xs">AQI <strong>${aqi.aqi}</strong></span>
                <span class="text-[10px]" style="color:${aqi.color}">${aqi.label_th}</span>
            </div>`;
            if (aqi.pm25) html += `<div class="text-slate-500 text-[10px]">PM2.5: ${aqi.pm25} µg/m³</div>`;
        }

        if (!html) html = '<div class="text-slate-500 text-[10px]">ไม่มีข้อมูล</div>';
        el.innerHTML = html;
    }

    // ─── Map Radius (based on zoom level) ───
    function getMapRadius() {
        if (!map) return 50;
        const zoom = map.getZoom();
        // Approximate radius in km based on zoom
        if (zoom >= 16) return 2;
        if (zoom >= 14) return 5;
        if (zoom >= 12) return 15;
        if (zoom >= 10) return 50;
        if (zoom >= 8) return 100;
        return 200;
    }

    // ─── Balloon Labels for stations/incidents ───
    let balloonLabels = [];
    let showBalloons = false;
    let showLabels = true;

    function toggleBalloons() {
        showBalloons = !showBalloons;
        const btn = document.getElementById('btn-balloons');
        btn.className = showBalloons
            ? 'metal-btn-accent px-2.5 py-1 rounded-full text-[10px] text-white'
            : 'metal-btn px-2.5 py-1 rounded-full text-[10px] text-slate-400';
        updateBalloonVisibility();
    }

    function toggleLabels() {
        showLabels = !showLabels;
        const btn = document.getElementById('btn-labels');
        btn.className = showLabels
            ? 'metal-btn-accent px-2.5 py-1 rounded-full text-[10px] text-white'
            : 'metal-btn px-2.5 py-1 rounded-full text-[10px] text-slate-400';

        stationMarkers.forEach(m => m.setVisible(showLabels && (currentFilter === 'all' || currentFilter === 'stations')));
        incidentMarkers.forEach(m => m.setVisible(showLabels && (currentFilter === 'all' || currentFilter === 'incidents')));
    }

    function updateBalloonVisibility() {
        balloonLabels.forEach(lbl => lbl.setMap(showBalloons ? map : null));
    }

    function addBalloonLabel(position, text, color = '#22c55e') {
        if (!google.maps.marker?.AdvancedMarkerElement) {
            // Fallback: use InfoWindow-like labels
            const label = new google.maps.Marker({
                position,
                map: showBalloons ? map : null,
                icon: {
                    path: 'M-8,-16 L8,-16 L8,0 L2,6 L-2,6 L-8,0 Z',
                    scale: 1.2,
                    fillColor: color,
                    fillOpacity: 0.9,
                    strokeColor: '#fff',
                    strokeWeight: 1,
                    anchor: new google.maps.Point(0, 6),
                    labelOrigin: new google.maps.Point(0, -8),
                },
                label: {
                    text: text.substring(0, 15),
                    color: '#fff',
                    fontSize: '9px',
                    fontWeight: 'bold',
                },
                clickable: false,
                zIndex: 100,
            });
            balloonLabels.push(label);
        }
    }

    // ─── Breaking News ───
    let breakingNewsData = [];

    async function loadBreakingNews() {
        try {
            const res = await fetch('/api/breaking-news');
            const data = await res.json();
            if (data.success && data.data?.length > 0) {
                breakingNewsData = data.data;
                const bar = document.getElementById('breaking-news-bar');
                const title = document.getElementById('breaking-news-title');
                const count = document.getElementById('breaking-news-count');

                title.textContent = breakingNewsData[0].title;
                count.textContent = breakingNewsData.length + ' ข่าว';
                bar.style.display = 'block';

                // Add markers for breaking news
                breakingNewsData.forEach(news => {
                    if (!news.latitude || !news.longitude) return;
                    const marker = new google.maps.Marker({
                        position: { lat: news.latitude, lng: news.longitude },
                        map: map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 12,
                            fillColor: '#dc2626',
                            fillOpacity: 0.8,
                            strokeColor: '#fbbf24',
                            strokeWeight: 3,
                        },
                        title: news.title,
                        zIndex: 500,
                    });

                    const photos = (news.image_urls || []).map(url =>
                        `<img src="${url}" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" onerror="this.remove()">`
                    ).join('');

                    const iw = new google.maps.InfoWindow({
                        content: `<div style="color:#000;max-width:280px;font-family:sans-serif;">
                            <div style="background:#dc2626;color:#fff;padding:4px 8px;border-radius:8px 8px 0 0;font-size:11px;font-weight:bold;">🔴 ข่าวด่วน — ${news.reporter_count} คนรายงาน</div>
                            <div style="padding:8px;">
                                <p style="font-size:13px;font-weight:bold;margin:0 0 4px;">${news.title}</p>
                                <p style="font-size:11px;color:#555;margin:0 0 6px;">${news.content?.substring(0, 150)}...</p>
                                ${photos ? '<div style="display:flex;gap:4px;flex-wrap:wrap;">' + photos + '</div>' : ''}
                                <p style="font-size:10px;color:#999;margin-top:4px;">— น้องหญิง รายงาน</p>
                            </div>
                        </div>`
                    });
                    marker.addListener('click', () => iw.open(map, marker));
                });

                renderBreakingNewsList();
            }
        } catch (e) {
            console.log('Breaking news load failed:', e);
        }
    }

    function toggleBreakingNews() {
        const panel = document.getElementById('breaking-news-panel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    function renderBreakingNewsList() {
        const list = document.getElementById('breaking-news-list');
        list.innerHTML = breakingNewsData.map(news => `
            <div class="p-3">
                <p class="text-xs font-medium text-white">${news.title}</p>
                <p class="text-[11px] text-slate-400 mt-1 line-clamp-3">${news.content || ''}</p>
                <div class="flex items-center gap-2 mt-2 text-[10px] text-slate-500">
                    <span>👥 ${news.reporter_count} คนรายงาน</span>
                    <span>•</span>
                    <span>${new Date(news.created_at).toLocaleString('th-TH')}</span>
                </div>
                ${(news.image_urls || []).length > 0 ? '<div class="flex gap-1 mt-2">' + news.image_urls.slice(0, 3).map(u => '<img src="' + u + '" class="w-16 h-16 object-cover rounded" onerror="this.remove()">').join('') + '</div>' : ''}
            </div>
        `).join('');
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
