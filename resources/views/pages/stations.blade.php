@extends('layouts.app')

@section('content')
<div class="min-h-screen" x-data="stationsPage">
    {{-- Hero Section --}}
    <div class="relative overflow-hidden px-4 py-6" style="background: linear-gradient(135deg, rgba(37,99,235,0.15) 0%, rgba(249,115,22,0.1) 100%);">
        <div class="relative z-10">
            <h1 class="text-xl font-bold text-chrome mb-1">ปั๊มน้ำมัน</h1>
            <p class="text-sm text-slate-400">ค้นหาปั๊มน้ำมันใกล้คุณ</p>
            <div class="flex gap-4 mt-3">
                <div class="text-center">
                    <div class="text-lg font-bold text-orange-500" id="station-count">--</div>
                    <div class="text-[10px] text-slate-500">ปั๊มทั้งหมด</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-bold text-blue-500" id="nearby-count">--</div>
                    <div class="text-[10px] text-slate-500">ใกล้คุณ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="px-4 py-3 space-y-3">
        {{-- Search Box --}}
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="station-search" placeholder="ค้นหาปั๊มน้ำมัน..."
                   class="metal-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none">
        </div>

        {{-- Radius Slider --}}
        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-500 whitespace-nowrap">รัศมี</span>
            <input type="range" id="radius-slider" min="1" max="50" value="10"
                   class="flex-1 h-1 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-orange-500">
            <span class="text-xs text-orange-500 font-medium whitespace-nowrap" id="radius-value">10 กม.</span>
        </div>

        {{-- Fuel Type Filter --}}
        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
            <button class="metal-btn-accent px-3 py-1.5 rounded-full text-xs font-medium text-white whitespace-nowrap" data-fuel="all">
                ทั้งหมด
            </button>
            <button class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300 whitespace-nowrap" data-fuel="gasohol95">
                แก๊สโซฮอล์ 95
            </button>
            <button class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300 whitespace-nowrap" data-fuel="gasohol91">
                แก๊สโซฮอล์ 91
            </button>
            <button class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300 whitespace-nowrap" data-fuel="diesel">
                ดีเซล
            </button>
            <button class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300 whitespace-nowrap" data-fuel="e20">
                E20
            </button>
            <button class="metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300 whitespace-nowrap" data-fuel="lpg">
                LPG
            </button>
        </div>
    </div>

    <div class="metal-divider mx-4"></div>

    {{-- Station List --}}
    <div class="px-4 py-3 space-y-3" id="station-list">
        {{-- Loading State --}}
        <div id="station-loading" class="text-center py-8">
            <div class="inline-block w-8 h-8 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin"></div>
            <p class="text-sm text-slate-500 mt-3">กำลังค้นหาปั๊มน้ำมัน...</p>
        </div>

        {{-- Empty State --}}
        <div id="station-empty" class="text-center py-8 hidden">
            <div class="text-4xl mb-3">⛽</div>
            <p class="text-sm text-slate-400">ไม่พบปั๊มน้ำมันในบริเวณนี้</p>
            <p class="text-xs text-slate-500 mt-1">ลองขยายรัศมีการค้นหา</p>
        </div>

        {{-- Station Cards (populated by JS/Livewire) --}}
        <template id="station-card-template">
            <div class="metal-panel metal-panel-hover rounded-xl p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-white truncate station-name"></h3>
                        <p class="text-xs text-slate-400 station-brand"></p>
                    </div>
                    <div class="text-right ml-3">
                        <span class="text-xs font-medium text-orange-500 station-distance"></span>
                    </div>
                </div>

                {{-- Fuel Prices Grid --}}
                <div class="grid grid-cols-3 gap-2 mb-3 station-fuels">
                    {{-- Populated dynamically --}}
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-2">
                    <button class="metal-btn flex-1 py-2 rounded-lg text-xs text-slate-300 flex items-center justify-center gap-1 btn-navigate">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        นำทาง
                    </button>
                    <button class="metal-btn-blue flex-1 py-2 rounded-lg text-xs text-white flex items-center justify-center gap-1 btn-detail">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        รายละเอียด
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('stationsPage', () => ({
            stations: [],
            loading: true,
            radius: 10,
            searchQuery: '',
            selectedFuel: 'all',
            userLat: 13.7563,
            userLng: 100.5018,

            async init() {
                await this.getUserLocation();
                await this.searchStations();

                // Radius slider
                const radiusSlider = document.getElementById('radius-slider');
                const radiusValue = document.getElementById('radius-value');
                radiusSlider.addEventListener('input', (e) => {
                    radiusValue.textContent = e.target.value + ' กม.';
                    this.radius = parseInt(e.target.value);
                });
                radiusSlider.addEventListener('change', () => {
                    this.searchStations();
                });

                // Search input
                const searchInput = document.getElementById('station-search');
                searchInput.addEventListener('input', (e) => {
                    this.searchQuery = e.target.value;
                    this.renderStations();
                });

                // Fuel filter buttons
                document.querySelectorAll('[data-fuel]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('[data-fuel]').forEach(b => {
                            b.className = 'metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300 whitespace-nowrap';
                        });
                        btn.className = 'metal-btn-accent px-3 py-1.5 rounded-full text-xs font-medium text-white whitespace-nowrap';
                        this.selectedFuel = btn.dataset.fuel;
                        this.renderStations();
                    });
                });
            },

            async getUserLocation() {
                return new Promise((resolve) => {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                this.userLat = pos.coords.latitude;
                                this.userLng = pos.coords.longitude;
                                resolve();
                            },
                            () => resolve()
                        );
                    } else {
                        resolve();
                    }
                });
            },

            async searchStations() {
                this.loading = true;
                this.showLoading(true);
                try {
                    const res = await axios.get('/api/stations', {
                        params: { lat: this.userLat, lng: this.userLng, radius: this.radius * 1000 }
                    });
                    if (res.data.success) {
                        this.stations = res.data.data || [];
                    } else {
                        this.stations = res.data.data || res.data || [];
                    }
                } catch (err) {
                    console.error('Failed to load stations:', err);
                    this.stations = [];
                } finally {
                    this.loading = false;
                    this.renderStations();
                }
            },

            get filteredStations() {
                return this.stations.filter(s => {
                    if (this.searchQuery && !s.name.toLowerCase().includes(this.searchQuery.toLowerCase())) return false;
                    if (this.selectedFuel !== 'all' && s.fuels) {
                        const hasFuel = s.fuels.some(f => f.type === this.selectedFuel);
                        if (!hasFuel) return false;
                    }
                    return true;
                });
            },

            showLoading(show) {
                const loadingEl = document.getElementById('station-loading');
                const emptyEl = document.getElementById('station-empty');
                if (loadingEl) loadingEl.classList.toggle('hidden', !show);
                if (emptyEl) emptyEl.classList.add('hidden');
            },

            escHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            },

            showStationDetail(station) {
                const lat = station.latitude || station.lat;
                const lng = station.longitude || station.lng;
                const distKm = station.distance;
                const distStr = distKm !== undefined && distKm !== null
                    ? (distKm >= 1 ? distKm.toFixed(1) + ' กม.' : Math.round(distKm * 1000) + ' เมตร')
                    : '';

                // Brand detection
                const stName = station.name || station.station_name || '';
                const brandKey = this.detectBrand(stName);
                const brandCfg = brandKey ? this.brandConfig[brandKey] : null;

                // Brand logo HTML
                const brandLogoHtml = `<img src="${brandCfg?.icon || '/images/brands/default.webp'}" alt="${brandCfg?.name || 'ปั๊ม'}" class="w-12 h-12 rounded-xl" onerror="this.outerHTML='<span class=\\'text-3xl\\'>⛽</span>'">`;

                // Fuel reports HTML
                let fuelsHtml = '';
                if (station.fuel_reports && station.fuel_reports.length > 0) {
                    fuelsHtml = '<div class="grid grid-cols-2 gap-2">' + station.fuel_reports.map(f => {
                        const statusColor = f.status === 'available' ? 'text-green-400 border-green-500/30' : f.status === 'low' ? 'text-yellow-400 border-yellow-500/30' : 'text-red-400 border-red-500/30';
                        const statusIcon = f.status === 'available' ? '🟢' : f.status === 'low' ? '🟡' : '🔴';
                        const statusText = f.status === 'available' ? 'มี' : f.status === 'low' ? 'เหลือน้อย' : 'หมด';
                        const fuelLabels = {gasohol95:'แก๊สโซฮอล์ 95',gasohol91:'แก๊สโซฮอล์ 91',e20:'E20',e85:'E85',diesel:'ดีเซล',diesel_b7:'ดีเซล B7',premium_diesel:'ดีเซลพรีเมียม',ngv:'NGV',lpg:'LPG'};
                        return `<div class="metal-panel rounded-lg p-2.5 border ${statusColor.split(' ')[1] || ''}">
                            <div class="text-xs text-slate-400 mb-0.5">${fuelLabels[f.fuel_type] || f.fuel_type}</div>
                            <div class="text-sm font-semibold ${statusColor.split(' ')[0]}">${statusIcon} ${statusText}</div>
                            ${f.price ? `<div class="text-xs text-orange-400 mt-0.5">฿${parseFloat(f.price).toFixed(2)}</div>` : ''}
                        </div>`;
                    }).join('') + '</div>';
                } else {
                    fuelsHtml = `
                        <div class="metal-panel rounded-xl p-4 text-center border border-dashed border-slate-600">
                            <p class="text-2xl mb-2">📋</p>
                            <p class="text-sm text-slate-400 font-medium">ยังไม่มีรายงานข้อมูลน้ำมัน</p>
                            <p class="text-xs text-slate-500 mt-1">เป็นคนแรกที่รายงานปั๊มนี้!</p>
                        </div>
                    `;
                }

                const facilitiesHtml = station.facilities ? Object.keys(station.facilities).map(f => {
                    const labels = {air_pump:'🌀 ที่เติมลม',restroom:'🚻 ห้องน้ำ',convenience:'🏪 ร้านสะดวกซื้อ',car_wash:'🚿 ล้างรถ',coffee:'☕ ร้านกาแฟ',wifi:'📶 WiFi',atm:'🏧 ATM',ev_charger:'🔌 ชาร์จ EV'};
                    return `<span class="metal-btn px-2.5 py-1 rounded-lg text-xs text-slate-300">${labels[f] || f}</span>`;
                }).join(' ') : '';

                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4';
                modal.innerHTML = `
                    <div class="fixed inset-0 bg-black/60" onclick="this.parentElement.remove()"></div>
                    <div class="relative metal-panel rounded-2xl p-5 w-full max-w-md max-h-[80vh] overflow-y-auto z-10">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center gap-3">
                                ${brandLogoHtml}
                                <div>
                                    <h3 class="text-lg font-bold text-white">${this.escHtml(stName) || 'ปั๊มน้ำมัน'}</h3>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        ${brandCfg ? `<span class="text-xs font-medium" style="color:${brandCfg.color}">${brandCfg.name}</span>` : ''}
                                        ${distStr ? `<span class="text-xs text-slate-500">${distStr}</span>` : ''}
                                        ${station.is_verified ? '<span class="text-xs text-blue-400">✅ ยืนยันแล้ว</span>' : ''}
                                    </div>
                                    ${station.vicinity ? `<p class="text-xs text-slate-500 mt-0.5">📍 ${this.escHtml(station.vicinity)}</p>` : ''}
                                </div>
                            </div>
                            <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-white text-xl leading-none">✕</button>
                        </div>
                        <div class="mb-4">
                            <h4 class="text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">สถานะน้ำมัน</h4>
                            ${fuelsHtml}
                        </div>
                        ${facilitiesHtml ? `<div class="mb-4"><h4 class="text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wider">สิ่งอำนวยความสะดวก</h4><div class="flex flex-wrap gap-1.5">${facilitiesHtml}</div></div>` : ''}
                        ${station.last_report_at ? `<p class="text-xs text-slate-500 mb-3">🕐 รายงานล่าสุด: ${new Date(station.last_report_at).toLocaleString('th-TH')}</p>` : ''}
                        <div class="flex gap-2">
                            ${lat && lng ? `<button onclick="window.open('https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}','_blank')" class="metal-btn-accent flex-1 py-2.5 rounded-xl text-sm text-white font-medium">🧭 นำทาง</button>` : ''}
                            <button onclick="window.location.href='/report'" class="metal-btn flex-1 py-2.5 rounded-xl text-sm text-slate-300">📝 รายงาน</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            },

            // Brand detection helper (matches maps.js detectBrand)
            brandConfig: {
                ptt:      { name: 'PTT',      color: '#1e3a8a', icon: '/images/brands/ptt.webp' },
                shell:    { name: 'Shell',     color: '#dd1d21', icon: '/images/brands/shell.webp' },
                bangchak: { name: 'Bangchak',  color: '#006838', icon: '/images/brands/bangchak.webp' },
                esso:     { name: 'Esso',      color: '#d62631', icon: '/images/brands/esso.webp' },
                caltex:   { name: 'Caltex',    color: '#c8102e', icon: '/images/brands/caltex.webp' },
                susco:    { name: 'Susco',     color: '#7c3aed', icon: '/images/brands/susco.webp' },
                pt:       { name: 'PT',        color: '#ea580c', icon: '/images/brands/pt.webp' },
                pure:     { name: 'PURE',      color: '#0284c7', icon: '/images/brands/default.webp' },
                irpc:     { name: 'IRPC',      color: '#0d9488', icon: '/images/brands/irpc.webp' },
            },
            detectBrand(name) {
                if (!name) return null;
                const n = name.toLowerCase();
                if (n.includes('ptt') || n.includes('ปตท'))             return 'ptt';
                if (n.includes('shell') || n.includes('เชลล์'))         return 'shell';
                if (n.includes('bangchak') || n.includes('บางจาก'))     return 'bangchak';
                if (n.includes('esso') || n.includes('เอสโซ'))         return 'esso';
                if (n.includes('caltex') || n.includes('คาลเท็กซ์'))   return 'caltex';
                if (n.includes('susco') || n.includes('ซัสโก้'))       return 'susco';
                if (n.includes('pt ') || n === 'pt')                     return 'pt';
                if (n.includes('pure') || n.includes('เพียว'))           return 'pure';
                if (n.includes('irpc'))                                  return 'irpc';
                return null;
            },

            renderStations() {
                const list = document.getElementById('station-list');
                const loadingEl = document.getElementById('station-loading');
                const emptyEl = document.getElementById('station-empty');
                const template = document.getElementById('station-card-template');

                // Remove old cards
                list.querySelectorAll('.station-card').forEach(el => el.remove());

                const filtered = this.filteredStations;

                // Update counts
                document.getElementById('station-count').textContent = this.stations.length;
                document.getElementById('nearby-count').textContent = filtered.length;

                if (loadingEl) loadingEl.classList.add('hidden');

                if (filtered.length === 0) {
                    if (emptyEl) emptyEl.classList.remove('hidden');
                    return;
                }
                if (emptyEl) emptyEl.classList.add('hidden');

                filtered.forEach(station => {
                    const card = template.content.cloneNode(true);
                    const wrapper = card.querySelector('.metal-panel');
                    wrapper.classList.add('station-card');

                    card.querySelector('.station-name').textContent = station.name || 'ปั๊มน้ำมัน';
                    card.querySelector('.station-brand').textContent = station.brand || '';

                    const distKm = station.distance; // distance is in KM from server
                    if (distKm !== undefined && distKm !== null) {
                        let distStr;
                        if (distKm >= 10) {
                            distStr = Math.round(distKm) + ' กม.';
                        } else if (distKm >= 1) {
                            distStr = distKm.toFixed(1) + ' กม.';
                        } else if (distKm >= 0.1) {
                            distStr = Math.round(distKm * 1000 / 10) * 10 + ' เมตร';
                        } else {
                            distStr = Math.round(distKm * 1000) + ' เมตร';
                        }
                        card.querySelector('.station-distance').textContent = distStr;
                    }

                    // Fuel prices
                    const fuelsGrid = card.querySelector('.station-fuels');
                    if (station.fuels && station.fuels.length > 0) {
                        station.fuels.forEach(fuel => {
                            const fuelEl = document.createElement('div');
                            fuelEl.className = 'metal-panel rounded-lg p-2 text-center';
                            fuelEl.innerHTML = `
                                <div class="text-[10px] text-slate-400">${fuel.name || fuel.type || ''}</div>
                                <div class="text-sm font-semibold text-orange-500">${fuel.price ? fuel.price.toFixed(2) : '-'}</div>
                            `;
                            fuelsGrid.appendChild(fuelEl);
                        });
                    }

                    // Navigate button
                    const navBtn = card.querySelector('.btn-navigate');
                    navBtn.addEventListener('click', () => {
                        const lat = station.latitude || station.lat;
                        const lng = station.longitude || station.lng;
                        if (lat && lng) {
                            window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
                        }
                    });

                    // Detail button — show modal
                    const detailBtn = card.querySelector('.btn-detail');
                    detailBtn.addEventListener('click', () => {
                        this.showStationDetail(station);
                    });

                    list.appendChild(card);
                });
            }
        }));
    });
</script>
@endpush
