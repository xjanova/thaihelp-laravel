@extends('layouts.app')

@section('content')
<div class="min-h-screen">
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
    // Radius slider
    const radiusSlider = document.getElementById('radius-slider');
    const radiusValue = document.getElementById('radius-value');
    radiusSlider.addEventListener('input', (e) => {
        radiusValue.textContent = e.target.value + ' กม.';
    });

    // Fuel type filter buttons
    document.querySelectorAll('[data-fuel]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('[data-fuel]').forEach(b => {
                b.className = 'metal-btn px-3 py-1.5 rounded-full text-xs font-medium text-slate-300 whitespace-nowrap';
            });
            btn.className = 'metal-btn-accent px-3 py-1.5 rounded-full text-xs font-medium text-white whitespace-nowrap';
        });
    });
</script>
@endpush
