@extends('layouts.app')

@section('content')
<div class="min-h-screen" x-data="tripPlanner()" x-init="init()">

    {{-- Header --}}
    <div class="metal-panel border-b border-slate-700 px-4 py-3">
        <h1 class="text-base font-bold text-white flex items-center gap-2">🗺️ วางแผนการเดินทาง</h1>
        <p class="text-[10px] text-slate-500 mt-0.5">น้องหญิงช่วยวางแผนเส้นทาง พร้อมเช็คปั๊ม/ชาร์จ EV/เหตุการณ์</p>
    </div>

    {{-- Form --}}
    <div class="p-4 space-y-3">
        {{-- Vehicle Type --}}
        <div class="flex gap-2">
            <button @click="vehicleType='car'" :class="vehicleType==='car' ? 'metal-btn-accent' : 'metal-btn'" class="flex-1 px-3 py-2 rounded-xl text-xs text-center">
                🚗 รถยนต์
            </button>
            <button @click="vehicleType='ev'" :class="vehicleType==='ev' ? 'metal-btn-accent' : 'metal-btn'" class="flex-1 px-3 py-2 rounded-xl text-xs text-center">
                🔌 รถ EV
            </button>
            <button @click="vehicleType='motorcycle'" :class="vehicleType==='motorcycle' ? 'metal-btn-accent' : 'metal-btn'" class="flex-1 px-3 py-2 rounded-xl text-xs text-center">
                🏍️ มอเตอร์ไซค์
            </button>
        </div>

        {{-- Origin --}}
        <div class="metal-panel rounded-xl p-3">
            <label class="text-[10px] text-slate-500 uppercase">📍 ต้นทาง</label>
            <div class="flex gap-2 mt-1">
                <input type="text" x-model="originText" placeholder="ค้นหาสถานที่ หรือใช้ GPS"
                       class="flex-1 bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none"
                       @keyup.enter="searchPlace('origin', originText)">
                <button @click="useMyLocation('origin')" class="metal-btn px-3 py-2 rounded-lg text-xs" title="ใช้ GPS">
                    📡
                </button>
            </div>
        </div>

        {{-- Destination --}}
        <div class="metal-panel rounded-xl p-3">
            <label class="text-[10px] text-slate-500 uppercase">🏁 ปลายทาง</label>
            <div class="flex gap-2 mt-1">
                <input type="text" x-model="destText" placeholder="ค้นหาสถานที่ปลายทาง"
                       class="flex-1 bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none"
                       @keyup.enter="searchPlace('dest', destText)">
                <button @click="swapLocations()" class="metal-btn px-3 py-2 rounded-lg text-xs" title="สลับ">
                    🔄
                </button>
            </div>
        </div>

        {{-- Plan Button --}}
        <button @click="planTrip()" :disabled="loading || !origin || !dest"
                class="w-full metal-btn-accent py-3 rounded-xl text-sm font-bold text-white disabled:opacity-50 flex items-center justify-center gap-2">
            <span x-show="!loading">🚀 วางแผนเส้นทาง</span>
            <span x-show="loading" class="flex items-center gap-2">
                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path d="M4 12a8 8 0 018-8V0" fill="currentColor" opacity="0.75"/></svg>
                กำลังวางแผน...
            </span>
        </button>
    </div>

    {{-- Results --}}
    <div x-show="result" x-transition class="px-4 pb-32 space-y-3">

        {{-- น้องหญิงสรุป --}}
        <div class="metal-panel rounded-xl p-3 border-l-4 border-pink-500" x-show="result?.ying_summary">
            <div class="flex items-start gap-2">
                <img src="/images/ying.webp" class="w-8 h-8 rounded-full object-cover" onerror="this.style.display='none'">
                <div>
                    <p class="text-[10px] text-pink-400 font-medium">น้องหญิง สรุปให้ค่ะ</p>
                    <p class="text-xs text-slate-300 whitespace-pre-line mt-1" x-text="result?.ying_summary"></p>
                </div>
            </div>
        </div>

        {{-- Fallback notice --}}
        <div x-show="result?.route?.is_fallback" class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-2 text-xs text-yellow-300 text-center">
            📏 ระยะทางเป็นค่าประมาณ (เส้นตรง x1.35) — เปิดใน Google Maps เพื่อดูเส้นทางจริง
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-3 gap-2" x-show="result?.summary">
            <div class="metal-panel rounded-xl p-2 text-center">
                <p class="text-lg font-bold text-white" x-text="(result?.summary?.distance_km || 0) + ' กม.'"></p>
                <p class="text-[10px] text-slate-500">ระยะทาง</p>
            </div>
            <div class="metal-panel rounded-xl p-2 text-center">
                <p class="text-lg font-bold text-white" x-text="result?.summary?.duration_min >= 60 ? (Math.round(result?.summary?.duration_min / 60 * 10) / 10 + ' ชม.') : (result?.summary?.duration_min + ' นาที')"></p>
                <p class="text-[10px] text-slate-500">เวลา</p>
            </div>
            <div class="metal-panel rounded-xl p-2 text-center">
                <p class="text-lg font-bold" :class="result?.summary?.has_warnings ? 'text-red-400' : 'text-green-400'"
                   x-text="result?.summary?.has_warnings ? '⚠️' : '✅'"></p>
                <p class="text-[10px] text-slate-500" x-text="result?.summary?.has_warnings ? 'มีเตือน' : 'ปลอดภัย'"></p>
            </div>
        </div>

        {{-- Fuel Stations --}}
        <div x-show="result?.fuel_stations?.length > 0">
            <h3 class="text-xs font-bold text-white mb-2">⛽ ปั๊มน้ำมันตลอดเส้นทาง (<span x-text="result?.fuel_stations?.length"></span>)</h3>
            <div class="space-y-1 max-h-40 overflow-y-auto">
                <template x-for="s in (result?.fuel_stations || []).slice(0, 10)" :key="s.id">
                    <div class="metal-panel rounded-lg px-3 py-2 flex items-center gap-2">
                        <span class="text-sm">⛽</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-white truncate" x-text="s.name || s.station_name"></p>
                            <p class="text-[10px] text-slate-500" x-text="s.brand || ''"></p>
                        </div>
                        <a :href="'https://www.google.com/maps/dir/?api=1&destination=' + s.latitude + ',' + s.longitude"
                           target="_blank" class="text-[10px] text-blue-400">🧭</a>
                    </div>
                </template>
            </div>
        </div>

        {{-- EV Chargers --}}
        <div x-show="vehicleType === 'ev' && result?.ev_chargers?.length > 0">
            <h3 class="text-xs font-bold text-white mb-2">🔌 สถานีชาร์จ EV (<span x-text="result?.ev_chargers?.length"></span>)</h3>
            <div class="space-y-1 max-h-40 overflow-y-auto">
                <template x-for="c in (result?.ev_chargers || []).slice(0, 10)" :key="c.id">
                    <div class="metal-panel rounded-lg px-3 py-2 flex items-center gap-2">
                        <span class="text-sm" x-text="c.speed_category === 'fast' ? '⚡' : '🔌'"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-white truncate" x-text="c.name"></p>
                            <div class="flex gap-1 mt-0.5">
                                <span class="text-[9px] px-1.5 py-0.5 rounded-full"
                                      :class="c.speed_category === 'fast' ? 'bg-yellow-500/20 text-yellow-400' : c.speed_category === 'medium' ? 'bg-blue-500/20 text-blue-400' : 'bg-slate-500/20 text-slate-400'"
                                      x-text="c.max_power_kw + ' kW'"></span>
                                <span class="text-[9px] text-slate-500" x-text="c.operator || ''"></span>
                            </div>
                        </div>
                        <a :href="'https://www.google.com/maps/dir/?api=1&destination=' + c.latitude + ',' + c.longitude"
                           target="_blank" class="text-[10px] text-blue-400">🧭</a>
                    </div>
                </template>
            </div>
        </div>

        {{-- Incidents / Warnings --}}
        <div x-show="result?.incidents?.length > 0">
            <h3 class="text-xs font-bold text-red-400 mb-2">⚠️ เหตุการณ์บนเส้นทาง (<span x-text="result?.incidents?.length"></span>)</h3>
            <div class="space-y-1">
                <template x-for="i in (result?.incidents || []).slice(0, 5)" :key="i.id">
                    <div class="metal-panel rounded-lg px-3 py-2 border-l-2 border-red-500">
                        <p class="text-xs text-white" x-text="i.title"></p>
                        <p class="text-[10px] text-slate-500" x-text="i.category + ' · ' + (i.severity || 'medium')"></p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Open in Google Maps --}}
        <a :href="'https://www.google.com/maps/dir/?api=1&origin=' + origin?.lat + ',' + origin?.lng + '&destination=' + dest?.lat + ',' + dest?.lng + '&travelmode=driving'"
           target="_blank" class="block w-full metal-btn py-3 rounded-xl text-sm text-center text-blue-400">
            🗺️ เปิดใน Google Maps
        </a>
    </div>
</div>

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\SiteSetting::get('google_maps_api_key') ?: config('services.google_maps.api_key') }}&libraries=places&language=th"></script>
<script>
function tripPlanner() {
    return {
        vehicleType: 'car',
        origin: null,
        dest: null,
        originText: '',
        destText: '',
        loading: false,
        result: null,

        init() {
            // Auto-set origin to current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    this.origin = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    this.originText = 'ตำแหน่งปัจจุบัน';
                });
            }
        },

        useMyLocation(which) {
            if (!navigator.geolocation) return alert('GPS ไม่พร้อมใช้งาน');
            navigator.geolocation.getCurrentPosition(pos => {
                const loc = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                if (which === 'origin') {
                    this.origin = loc;
                    this.originText = 'ตำแหน่งปัจจุบัน';
                } else {
                    this.dest = loc;
                    this.destText = 'ตำแหน่งปัจจุบัน';
                }
            });
        },

        async searchPlace(which, text) {
            if (!text || text.length < 2) return;
            try {
                const geocoder = new google.maps.Geocoder();
                const result = await new Promise((resolve, reject) => {
                    geocoder.geocode({ address: text + ' ประเทศไทย' }, (results, status) => {
                        if (status === 'OK' && results[0]) resolve(results[0]);
                        else reject(status);
                    });
                });
                const loc = {
                    lat: result.geometry.location.lat(),
                    lng: result.geometry.location.lng(),
                };
                if (which === 'origin') {
                    this.origin = loc;
                    this.originText = result.formatted_address;
                } else {
                    this.dest = loc;
                    this.destText = result.formatted_address;
                }
            } catch (e) {
                alert('ไม่พบสถานที่ "' + text + '" กรุณาลองใหม่');
            }
        },

        swapLocations() {
            [this.origin, this.dest] = [this.dest, this.origin];
            [this.originText, this.destText] = [this.destText, this.originText];
        },

        // Haversine distance in km (client-side fallback)
        haversine(lat1, lng1, lat2, lng2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },

        async planTrip() {
            if (!this.origin || !this.dest) return alert('กรุณาระบุต้นทางและปลายทาง');
            this.loading = true;
            this.result = null;

            try {
                const res = await fetch('/api/trip/plan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({
                        origin_lat: this.origin.lat,
                        origin_lng: this.origin.lng,
                        dest_lat: this.dest.lat,
                        dest_lng: this.dest.lng,
                        vehicle_type: this.vehicleType,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.result = data.data;

                    // Client-side fallback: if distance is still 0, calculate via Haversine
                    if (!this.result.summary?.distance_km || this.result.summary.distance_km <= 0) {
                        const straight = this.haversine(this.origin.lat, this.origin.lng, this.dest.lat, this.dest.lng);
                        const roadDist = Math.round(straight * 1.35 * 10) / 10;
                        const durationMin = Math.max(1, Math.round(roadDist / 60 * 60));
                        this.result.summary = {
                            ...this.result.summary,
                            distance_km: roadDist,
                            duration_min: durationMin,
                        };
                        if (this.result.route) this.result.route.is_fallback = true;
                    }
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (e) {
                // Full client-side fallback if API fails entirely
                const straight = this.haversine(this.origin.lat, this.origin.lng, this.dest.lat, this.dest.lng);
                const roadDist = Math.round(straight * 1.35 * 10) / 10;
                const durationMin = Math.max(1, Math.round(roadDist / 60 * 60));
                this.result = {
                    route: { is_fallback: true },
                    summary: {
                        distance_km: roadDist,
                        duration_min: durationMin,
                        fuel_stations_count: 0,
                        ev_chargers_count: 0,
                        incidents_count: 0,
                        danger_zones_count: 0,
                        has_warnings: false,
                    },
                    fuel_stations: [],
                    ev_chargers: [],
                    incidents: [],
                    danger_zones: [],
                    ying_summary: `🗺️ ระยะทางประมาณ ${roadDist} กม. ใช้เวลาประมาณ ${durationMin} นาทีค่ะ\n📏 ระยะทางเป็นค่าประมาณ (คำนวณจากพิกัด)\n— น้องหญิง 💕`,
                };
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
@endsection
