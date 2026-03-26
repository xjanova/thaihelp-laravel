@extends('layouts.app')

@section('content')
<div class="min-h-screen p-4 pb-32 space-y-4" x-data="fuelPricesPage()" x-init="load()">

    {{-- Header --}}
    <div class="text-center mb-4">
        <h1 class="text-lg font-bold text-white flex items-center justify-center gap-2">
            <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h10a1 1 0 011 1v10a2 2 0 01-2 2H5a2 2 0 01-2-2V6zm10 3h2.5a1.5 1.5 0 011.5 1.5v4a1.5 1.5 0 01-1.5 1.5H13m4-7l2-2m0 0v4m-6-8V3m-4 0v2"/>
            </svg>
            ราคาน้ำมันวันนี้
        </h1>
        <p class="text-[10px] text-slate-500 mt-1" x-text="date"></p>
        <p class="text-[9px] mt-0.5" :class="isFallback ? 'text-yellow-500' : 'text-emerald-500'">
            <span x-show="!isFallback">&#x2705; ข้อมูลจาก สนพ./บางจาก (อัปเดตทุก 6 ชม.)</span>
            <span x-show="isFallback">&#x26A0;&#xFE0F; ราคาโดยประมาณ (ไม่สามารถเชื่อมต่อแหล่งข้อมูลได้)</span>
        </p>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex flex-col items-center justify-center py-16 gap-3">
        <div class="w-10 h-10 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-slate-400 text-sm">กำลังดึงราคาน้ำมัน...</p>
    </div>

    {{-- Error --}}
    <div x-show="error && !loading" class="text-center py-12">
        <p class="text-red-400 text-sm mb-3" x-text="error"></p>
        <button @click="load()" class="metal-btn-accent px-4 py-2 rounded-lg text-sm text-white">ลองใหม่</button>
    </div>

    <template x-if="!loading && !error && Object.keys(prices).length > 0">
        <div class="space-y-4">

            {{-- Price Cards Grid --}}
            <div class="grid grid-cols-1 gap-2">
                <template x-for="ft in fuelTypes" :key="ft.key">
                    <div x-show="prices[ft.key]"
                         class="metal-panel rounded-xl p-3 border transition-all duration-200"
                         :class="selectedType === ft.key ? 'border-orange-500/50 bg-orange-500/5' : 'border-slate-700/50'"
                         @click="selectedType = ft.key; loadHistory()">

                        <div class="flex items-center justify-between">
                            {{-- Fuel info --}}
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg"
                                     :class="ft.color">
                                    <span x-text="ft.icon"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white" x-text="ft.label"></p>
                                    <p class="text-[9px] text-slate-500" x-text="prices[ft.key]?.source === 'bangchak' ? 'บางจาก' : 'สนพ.'"></p>
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="text-right">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-orange-400" x-text="prices[ft.key]?.price?.toFixed(2) || '-'"></span>
                                    <span class="text-[10px] text-slate-500">บาท/ลิตร</span>
                                </div>
                                <p class="text-[9px] text-slate-600" x-text="'อัปเดต ' + (prices[ft.key]?.updated_at || '')"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Price Comparison Bar --}}
            <div class="metal-panel rounded-xl p-4 border border-slate-700/50">
                <h3 class="text-xs font-bold text-white mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    เปรียบเทียบราคาทุกชนิด
                </h3>
                <div class="space-y-2">
                    <template x-for="ft in sortedByPrice" :key="ft.key">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-slate-400 w-24 truncate" x-text="ft.label"></span>
                            <div class="flex-1 h-5 bg-slate-800 rounded-full overflow-hidden relative">
                                <div class="h-full rounded-full transition-all duration-700"
                                     :style="`width: ${ft.pct}%`"
                                     :class="ft.cheapest ? 'bg-gradient-to-r from-emerald-600 to-emerald-400' : (ft.expensive ? 'bg-gradient-to-r from-red-600 to-red-400' : 'bg-gradient-to-r from-orange-600 to-orange-400')">
                                </div>
                            </div>
                            <span class="text-xs font-bold w-14 text-right"
                                  :class="ft.cheapest ? 'text-emerald-400' : (ft.expensive ? 'text-red-400' : 'text-orange-400')"
                                  x-text="ft.price.toFixed(2)"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- History Chart --}}
            <div class="metal-panel rounded-xl p-4 border border-slate-700/50">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2v-5z"/>
                        </svg>
                        กราฟราคาย้อนหลัง 30 วัน
                    </h3>
                    <span class="text-[9px] text-slate-500 px-2 py-0.5 rounded-full bg-slate-800" x-text="selectedTypeLabel"></span>
                </div>
                <div class="flex gap-1.5 overflow-x-auto pb-2 mb-3 scrollbar-hide">
                    <template x-for="ft in fuelTypes" :key="'tab-'+ft.key">
                        <button x-show="prices[ft.key]"
                                @click="selectedType = ft.key; loadHistory()"
                                :class="selectedType === ft.key ? 'metal-btn-accent text-white' : 'metal-btn text-slate-400'"
                                class="px-2.5 py-1 rounded-full text-[10px] whitespace-nowrap transition-all">
                            <span x-text="ft.shortLabel || ft.label"></span>
                        </button>
                    </template>
                </div>
                <div class="relative" style="height: 200px;">
                    <canvas id="priceChart"></canvas>
                </div>
                <div x-show="historyLoading" class="flex justify-center py-4">
                    <div class="w-5 h-5 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>

            {{-- Ying Summary --}}
            <div class="metal-panel rounded-xl p-3 border-l-4 border-orange-500">
                <div class="flex items-start gap-2.5">
                    <div class="w-9 h-9 rounded-full overflow-hidden ring-2 ring-orange-500/50 flex-shrink-0">
                        <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-orange-400 mb-1">น้องหญิงสรุปให้ค่ะ</p>
                        <p class="text-[11px] text-slate-300 leading-relaxed" x-text="getYingSummary()"></p>
                    </div>
                </div>
            </div>

            {{-- Auto-refresh --}}
            <div class="text-center text-[9px] text-slate-600 pb-4">
                อัปเดตล่าสุด: <span x-text="lastRefresh"></span> | รีเฟรชอัตโนมัติทุก 1 ชั่วโมง
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
function fuelPricesPage() {
    return {
        prices: {},
        history: [],
        date: '',
        lastRefresh: '',
        isFallback: false,
        selectedType: 'diesel',
        chart: null,
        loading: true,
        historyLoading: false,
        error: null,
        refreshInterval: null,

        fuelTypes: [
            { key: 'diesel',         label: 'ดีเซล',            shortLabel: 'ดีเซล',   icon: '\u26FD', color: 'bg-blue-500/20' },
            { key: 'diesel_b7',      label: 'ดีเซล B7',         shortLabel: 'B7',      icon: '\u26FD', color: 'bg-sky-500/20' },
            { key: 'premium_diesel', label: 'ดีเซลพรีเมียม',     shortLabel: 'พรีเมียม', icon: '\u2B50', color: 'bg-amber-500/20' },
            { key: 'gasohol95',      label: '\u0E41\u0E01\u0E4A\u0E2A\u0E42\u0E0B\u0E2E\u0E2D\u0E25\u0E4C 95', shortLabel: 'G95', icon: '\u{1F7E2}', color: 'bg-emerald-500/20' },
            { key: 'gasohol91',      label: '\u0E41\u0E01\u0E4A\u0E2A\u0E42\u0E0B\u0E2E\u0E2D\u0E25\u0E4C 91', shortLabel: 'G91', icon: '\u{1F7E1}', color: 'bg-yellow-500/20' },
            { key: 'e20',            label: 'E20',               shortLabel: 'E20',     icon: '\u{1F7E0}', color: 'bg-orange-500/20' },
            { key: 'e85',            label: 'E85',               shortLabel: 'E85',     icon: '\u{1F7E3}', color: 'bg-purple-500/20' },
            { key: 'ngv',            label: 'NGV',               shortLabel: 'NGV',     icon: '\u{1F535}', color: 'bg-cyan-500/20' },
            { key: 'lpg',            label: 'LPG',               shortLabel: 'LPG',     icon: '\u{1F534}', color: 'bg-red-500/20' },
        ],

        get selectedTypeLabel() {
            const ft = this.fuelTypes.find(f => f.key === this.selectedType);
            return ft ? ft.label : this.selectedType;
        },

        get sortedByPrice() {
            const items = [];
            const allPrices = [];

            for (const ft of this.fuelTypes) {
                if (this.prices[ft.key]) {
                    allPrices.push(this.prices[ft.key].price);
                }
            }
            const maxP = Math.max(...allPrices, 1);
            const minP = Math.min(...allPrices);
            const maxPVal = Math.max(...allPrices);
            const minPVal = Math.min(...allPrices);

            for (const ft of this.fuelTypes) {
                if (!this.prices[ft.key]) continue;
                const price = this.prices[ft.key].price;
                items.push({
                    key: ft.key,
                    label: ft.label,
                    price: price,
                    pct: maxP > 0 ? Math.max((price / maxP) * 100, 15) : 0,
                    cheapest: price === minPVal,
                    expensive: price === maxPVal,
                });
            }

            return items.sort((a, b) => a.price - b.price);
        },

        async load() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('/api/fuel-prices');
                if (!res.ok) throw new Error('ไม่สามารถโหลดข้อมูลได้');
                const json = await res.json();
                if (!json.success) throw new Error('เซิร์ฟเวอร์ตอบกลับผิดพลาด');

                this.prices = json.data || {};
                this.isFallback = json.is_fallback || false;
                this.date = this.formatDate(json.date);
                this.lastRefresh = new Date().toLocaleTimeString('th-TH');

                // Auto-select first available type
                if (!this.prices[this.selectedType]) {
                    const available = Object.keys(this.prices)[0];
                    if (available) this.selectedType = available;
                }

                this.loading = false;
                await this.loadHistory();

                if (!this.refreshInterval) {
                    this.refreshInterval = setInterval(() => this.load(), 3600000);
                }
            } catch (e) {
                this.error = e.message || 'เกิดข้อผิดพลาด';
                this.loading = false;
            }
        },

        async loadHistory() {
            this.historyLoading = true;
            try {
                const res = await fetch(`/api/fuel-prices/history?type=${this.selectedType}&days=30`);
                const json = await res.json();
                this.history = json.data || [];
            } catch (e) {
                this.history = [];
            }
            this.historyLoading = false;
            this.$nextTick(() => this.renderChart());
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString('th-TH', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
            } catch {
                return dateStr;
            }
        },

        renderChart() {
            const canvas = document.getElementById('priceChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            if (this.chart) this.chart.destroy();
            if (this.history.length === 0) return;

            const labels = this.history.map(h => {
                const d = new Date(h.date);
                return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
            });
            const data = this.history.map(h => h.avg_price);

            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(249, 115, 22, 0.3)');
            gradient.addColorStop(1, 'rgba(249, 115, 22, 0)');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: this.selectedTypeLabel,
                        data: data,
                        borderColor: '#f97316',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#f97316',
                        pointHoverBorderColor: '#1e293b',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#94a3b8',
                            bodyColor: '#f1f5f9',
                            borderColor: 'rgba(249, 115, 22, 0.3)',
                            borderWidth: 1,
                            cornerRadius: 8,
                            titleFont: { size: 10 },
                            bodyFont: { size: 12, weight: 'bold' },
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: (ctx) => ctx.parsed.y.toFixed(2) + ' \u0E1A\u0E32\u0E17/\u0E25\u0E34\u0E15\u0E23'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#475569', font: { size: 9 }, maxRotation: 0, maxTicksLimit: 7 },
                            grid: { color: 'rgba(51, 65, 85, 0.2)' },
                            border: { display: false },
                        },
                        y: {
                            ticks: { color: '#475569', font: { size: 9 }, callback: v => v.toFixed(2) },
                            grid: { color: 'rgba(51, 65, 85, 0.2)' },
                            border: { display: false },
                        }
                    }
                }
            });
        },

        getYingSummary() {
            const types = this.fuelTypes.filter(ft => this.prices[ft.key]);
            if (types.length === 0) return '\u0E22\u0E31\u0E07\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E04\u0E48\u0E30';

            const sorted = types.map(ft => ({
                label: ft.label,
                price: this.prices[ft.key].price
            })).sort((a, b) => a.price - b.price);

            const cheapest = sorted[0];
            const expensive = sorted[sorted.length - 1];
            const diesel = this.prices['diesel'];
            const g95 = this.prices['gasohol95'];

            let summary = '';
            if (diesel) {
                summary += `\u0E14\u0E35\u0E40\u0E0B\u0E25\u0E27\u0E31\u0E19\u0E19\u0E35\u0E49 ${diesel.price.toFixed(2)} \u0E1A\u0E32\u0E17/\u0E25\u0E34\u0E15\u0E23`;
            }
            if (g95) {
                summary += `${summary ? ' ' : ''}\u0E41\u0E01\u0E4A\u0E2A\u0E42\u0E0B\u0E2E\u0E2D\u0E25\u0E4C 95 \u0E2D\u0E22\u0E39\u0E48\u0E17\u0E35\u0E48 ${g95.price.toFixed(2)} \u0E1A\u0E32\u0E17\u0E04\u0E48\u0E30`;
            }
            if (cheapest && expensive && cheapest.label !== expensive.label) {
                summary += ` \u0E16\u0E39\u0E01\u0E2A\u0E38\u0E14\u0E04\u0E37\u0E2D${cheapest.label} ${cheapest.price.toFixed(2)} \u0E1A\u0E32\u0E17 \u0E41\u0E1E\u0E07\u0E2A\u0E38\u0E14\u0E04\u0E37\u0E2D${expensive.label} ${expensive.price.toFixed(2)} \u0E1A\u0E32\u0E17\u0E04\u0E48\u0E30`;
            }

            if (this.isFallback) {
                summary += ' (\u0E23\u0E32\u0E04\u0E32\u0E42\u0E14\u0E22\u0E1B\u0E23\u0E30\u0E21\u0E32\u0E13 \u0E23\u0E2D\u0E2D\u0E31\u0E1B\u0E40\u0E14\u0E17\u0E04\u0E48\u0E30)';
            }

            return summary || '\u0E44\u0E21\u0E48\u0E21\u0E35\u0E02\u0E49\u0E2D\u0E21\u0E39\u0E25\u0E04\u0E48\u0E30';
        },

        destroy() {
            if (this.refreshInterval) clearInterval(this.refreshInterval);
            if (this.chart) this.chart.destroy();
        }
    };
}
</script>
@endpush
