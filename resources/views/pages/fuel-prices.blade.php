@extends('layouts.app')

@section('content')
<div class="min-h-screen p-4 pb-32 space-y-4" x-data="fuelPricesPage()" x-init="load()">

    {{-- Header --}}
    <div class="text-center mb-4">
        <h1 class="text-lg font-bold text-white">&#9981; ราคาน้ำมันวันนี้</h1>
        <p class="text-[10px] text-slate-500" x-text="'อัพเดท: ' + date"></p>
    </div>

    {{-- Loading spinner --}}
    <div x-show="loading" class="flex justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- Fuel type tabs --}}
    <div x-show="!loading" class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
        <template x-for="ft in fuelTypes" :key="ft.key">
            <button @click="selectedType = ft.key; loadHistory()"
                    :class="selectedType === ft.key
                        ? 'metal-btn-accent text-white shadow-lg shadow-pink-500/20'
                        : 'metal-btn text-slate-400 hover:text-slate-200'"
                    class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition-all duration-200">
                <span x-text="ft.label"></span>
            </button>
        </template>
    </div>

    {{-- Today's prices grid --}}
    <div x-show="!loading" class="grid grid-cols-2 gap-2">
        <template x-for="item in filteredPrices" :key="item.brand">
            <div class="metal-panel rounded-xl p-3 relative overflow-hidden transition-all duration-300 hover:scale-[1.02]"
                 :class="item.price < avgPrice ? 'border border-emerald-500/30' : (item.price > avgPrice ? 'border border-red-500/30' : 'border border-slate-700/50')">

                {{-- Color indicator strip --}}
                <div class="absolute top-0 left-0 right-0 h-0.5"
                     :class="item.price < avgPrice ? 'bg-emerald-500' : (item.price > avgPrice ? 'bg-red-500' : 'bg-slate-600')">
                </div>

                {{-- Brand logo & name --}}
                <div class="flex items-center gap-2 mb-2">
                    <img :src="brandLogos[item.brand] || '/images/brands/default.png'"
                         :alt="item.brand"
                         class="w-8 h-8 rounded-lg"
                         onerror="this.outerHTML='⛽'">
                    <div>
                        <p class="text-[11px] font-bold text-white leading-tight" x-text="brandNames[item.brand] || item.brand"></p>
                        <p class="text-[9px] text-slate-500" x-text="selectedTypeLabel"></p>
                    </div>
                </div>

                {{-- Price --}}
                <div class="flex items-end justify-between">
                    <div>
                        <span class="text-xl font-black"
                              :class="item.price < avgPrice ? 'text-emerald-400' : (item.price > avgPrice ? 'text-red-400' : 'text-white')"
                              x-text="item.price.toFixed(2)"></span>
                        <span class="text-[9px] text-slate-500 ml-0.5">บาท/ลิตร</span>
                    </div>

                    {{-- Change indicator --}}
                    <div x-show="item.change !== 0" class="flex items-center gap-0.5"
                         :class="item.change > 0 ? 'text-red-400' : 'text-emerald-400'">
                        <svg x-show="item.change > 0" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <svg x-show="item.change < 0" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-[10px] font-bold" x-text="Math.abs(item.change).toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- No data state --}}
    <div x-show="!loading && filteredPrices.length === 0" class="metal-panel rounded-xl p-6 text-center">
        <p class="text-slate-500 text-sm">ไม่พบข้อมูลราคาน้ำมันประเภทนี้</p>
    </div>

    {{-- Price summary --}}
    <div x-show="!loading && filteredPrices.length > 0" class="metal-panel rounded-xl p-3">
        <h3 class="text-[11px] font-bold text-slate-400 mb-2 uppercase tracking-wider">สรุปราคา <span x-text="selectedTypeLabel" class="text-white"></span></h3>
        <div class="grid grid-cols-3 gap-3">
            {{-- Average --}}
            <div class="text-center">
                <p class="text-[9px] text-slate-500 mb-0.5">เฉลี่ย</p>
                <p class="text-sm font-black text-amber-400" x-text="avgPrice.toFixed(2)"></p>
                <p class="text-[8px] text-slate-600">บาท/ลิตร</p>
            </div>
            {{-- Min --}}
            <div class="text-center">
                <p class="text-[9px] text-slate-500 mb-0.5">ต่ำสุด</p>
                <p class="text-sm font-black text-emerald-400" x-text="minPrice.toFixed(2)"></p>
                <p class="text-[8px] text-emerald-700" x-text="minBrand"></p>
            </div>
            {{-- Max --}}
            <div class="text-center">
                <p class="text-[9px] text-slate-500 mb-0.5">สูงสุด</p>
                <p class="text-sm font-black text-red-400" x-text="maxPrice.toFixed(2)"></p>
                <p class="text-[8px] text-red-700" x-text="maxBrand"></p>
            </div>
        </div>
        {{-- Price range bar --}}
        <div class="mt-3 px-2">
            <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden relative">
                <div class="absolute inset-0 rounded-full"
                     style="background: linear-gradient(to right, #10b981, #f59e0b, #ef4444);"></div>
            </div>
            <div class="flex justify-between mt-1">
                <span class="text-[8px] text-emerald-500" x-text="minPrice.toFixed(2)"></span>
                <span class="text-[8px] text-red-500" x-text="maxPrice.toFixed(2)"></span>
            </div>
        </div>
    </div>

    {{-- History chart --}}
    <div x-show="!loading" class="metal-panel rounded-xl p-3">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold text-white">&#128200; กราฟราคาย้อนหลัง 30 วัน</h3>
            <span class="text-[9px] text-slate-600" x-text="selectedTypeLabel"></span>
        </div>
        <div class="relative" style="height: 200px;">
            <canvas id="priceChart"></canvas>
        </div>
        <div x-show="historyLoading" class="absolute inset-0 flex items-center justify-center">
            <svg class="animate-spin h-5 w-5 text-pink-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    {{-- Ying summary --}}
    <div x-show="!loading && filteredPrices.length > 0" class="metal-panel rounded-xl p-3 border-l-4 border-pink-500">
        <div class="flex items-start gap-2">
            <div class="w-8 h-8 rounded-full bg-pink-500/20 flex items-center justify-center flex-shrink-0">
                <span class="text-sm">&#128135;&#8205;&#9792;&#65039;</span>
            </div>
            <div>
                <p class="text-[10px] font-bold text-pink-400 mb-1">น้องหญิงสรุปให้ค่ะ</p>
                <p class="text-[11px] text-slate-300 leading-relaxed" x-text="getYingSummary()"></p>
            </div>
        </div>
    </div>

    {{-- Auto-refresh indicator --}}
    <div class="text-center">
        <p class="text-[9px] text-slate-700">รีเฟรชอัตโนมัติทุก 1 ชั่วโมง</p>
    </div>
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
        selectedType: 'diesel',
        chart: null,
        loading: true,
        historyLoading: false,
        refreshInterval: null,

        fuelTypes: [
            { key: 'diesel', label: 'ดีเซล' },
            { key: 'diesel_b7', label: 'ดีเซล B7' },
            { key: 'gasohol95', label: 'แก๊สโซฮอล์ 95' },
            { key: 'gasohol91', label: 'แก๊สโซฮอล์ 91' },
            { key: 'e20', label: 'E20' },
            { key: 'e85', label: 'E85' },
            { key: 'premium_diesel', label: 'ดีเซลพรีเมียม' },
        ],

        brandNames: {
            'ptt': 'PTT Station',
            'bcp': 'บางจาก',
            'shell': 'Shell',
            'esso': 'Esso',
            'caltex': 'Caltex',
            'pt': 'PT',
            'susco': 'ซัสโก้',
            'pure': 'เพียว',
            'irpc': 'IRPC',
        },

        brandLogos: {
            'ptt': '/images/brands/ptt.png',
            'bcp': '/images/brands/bangchak.png',
            'shell': '/images/brands/shell.png',
            'esso': '/images/brands/esso.png',
            'caltex': '/images/brands/caltex.png',
            'pt': '/images/brands/pt.png',
            'susco': '/images/brands/susco.png',
            'pure': '/images/brands/default.png',
            'irpc': '/images/brands/irpc.png',
        },

        get selectedTypeLabel() {
            const ft = this.fuelTypes.find(f => f.key === this.selectedType);
            return ft ? ft.label : this.selectedType;
        },

        get filteredPrices() {
            if (!this.prices[this.selectedType]) return [];
            return this.prices[this.selectedType].sort((a, b) => a.price - b.price);
        },

        get avgPrice() {
            const items = this.filteredPrices;
            if (items.length === 0) return 0;
            return items.reduce((sum, i) => sum + i.price, 0) / items.length;
        },

        get minPrice() {
            const items = this.filteredPrices;
            if (items.length === 0) return 0;
            return Math.min(...items.map(i => i.price));
        },

        get maxPrice() {
            const items = this.filteredPrices;
            if (items.length === 0) return 0;
            return Math.max(...items.map(i => i.price));
        },

        get minBrand() {
            const items = this.filteredPrices;
            if (items.length === 0) return '';
            const min = items.reduce((a, b) => a.price < b.price ? a : b);
            return this.brandNames[min.brand] || min.brand;
        },

        get maxBrand() {
            const items = this.filteredPrices;
            if (items.length === 0) return '';
            const max = items.reduce((a, b) => a.price > b.price ? a : b);
            return this.brandNames[max.brand] || max.brand;
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('/api/fuel-prices');
                const data = await res.json();
                this.prices = data.data || {};
                this.date = data.date || new Date().toLocaleDateString('th-TH', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });

                // Auto-select first available type if current has no data
                if (!this.prices[this.selectedType] || this.prices[this.selectedType].length === 0) {
                    const available = Object.keys(this.prices).find(k => this.prices[k].length > 0);
                    if (available) this.selectedType = available;
                }
            } catch (e) {
                console.error('Failed to load fuel prices:', e);
                // Use demo data for preview
                this.prices = this.getDemoData();
                this.date = new Date().toLocaleDateString('th-TH', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
            }
            this.loading = false;
            await this.loadHistory();

            // Auto-refresh every hour
            this.refreshInterval = setInterval(() => this.load(), 3600000);
        },

        async loadHistory() {
            this.historyLoading = true;
            try {
                const res = await fetch(`/api/fuel-prices/history?type=${this.selectedType}&days=30`);
                const data = await res.json();
                this.history = data.data || [];
            } catch (e) {
                console.error('Failed to load price history:', e);
                this.history = this.getDemoHistory();
            }
            this.historyLoading = false;
            this.$nextTick(() => this.renderChart());
        },

        renderChart() {
            const canvas = document.getElementById('priceChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            if (this.chart) {
                this.chart.destroy();
            }

            if (this.history.length === 0) return;

            const labels = this.history.map(h => {
                const d = new Date(h.date);
                return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
            });
            const prices = this.history.map(h => h.avg_price);

            // Gradient fill
            const gradient = ctx.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(236, 72, 153, 0.3)');
            gradient.addColorStop(0.5, 'rgba(236, 72, 153, 0.1)');
            gradient.addColorStop(1, 'rgba(236, 72, 153, 0)');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: this.selectedTypeLabel,
                        data: prices,
                        borderColor: '#ec4899',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#ec4899',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#94a3b8',
                            bodyColor: '#f1f5f9',
                            borderColor: '#334155',
                            borderWidth: 1,
                            titleFont: { size: 10 },
                            bodyFont: { size: 12, weight: 'bold' },
                            padding: 8,
                            displayColors: false,
                            callbacks: {
                                label: (ctx) => ctx.parsed.y.toFixed(2) + ' บาท/ลิตร'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#475569',
                                font: { size: 9 },
                                maxRotation: 0,
                                maxTicksLimit: 7,
                            },
                            grid: {
                                color: 'rgba(51, 65, 85, 0.3)',
                                drawBorder: false,
                            },
                            border: { display: false },
                        },
                        y: {
                            ticks: {
                                color: '#475569',
                                font: { size: 9 },
                                callback: (v) => v.toFixed(2),
                            },
                            grid: {
                                color: 'rgba(51, 65, 85, 0.3)',
                                drawBorder: false,
                            },
                            border: { display: false },
                        }
                    }
                }
            });
        },

        getYingSummary() {
            const items = this.filteredPrices;
            if (items.length === 0) return 'ยังไม่มีข้อมูลค่ะ';

            const min = items.reduce((a, b) => a.price < b.price ? a : b);
            const max = items.reduce((a, b) => a.price > b.price ? a : b);
            const minName = this.brandNames[min.brand] || min.brand;
            const maxName = this.brandNames[max.brand] || max.brand;
            const avg = this.avgPrice;
            const diff = (max.price - min.price).toFixed(2);
            const label = this.selectedTypeLabel;

            let trend = '';
            if (this.history.length >= 2) {
                const last = this.history[this.history.length - 1]?.avg_price || 0;
                const prev = this.history[this.history.length - 2]?.avg_price || 0;
                if (last > prev) trend = ' แนวโน้มราคาปรับขึ้นค่ะ';
                else if (last < prev) trend = ' แนวโน้มราคาปรับลดลงค่ะ';
                else trend = ' ราคาทรงตัวค่ะ';
            }

            return `${label}วันนี้ ราคาเฉลี่ย ${avg.toFixed(2)} บาท/ลิตร ` +
                   `ถูกสุดที่ ${minName} ${min.price.toFixed(2)} บาท ` +
                   `แพงสุดที่ ${maxName} ${max.price.toFixed(2)} บาท ` +
                   `ต่างกัน ${diff} บาท${trend}`;
        },

        getDemoData() {
            const brands = ['ptt', 'bcp', 'shell', 'esso', 'caltex', 'pt'];
            const types = {
                diesel: { base: 29.94, range: 1.5 },
                diesel_b7: { base: 29.44, range: 1.2 },
                gasohol95: { base: 36.45, range: 2.0 },
                gasohol91: { base: 35.98, range: 1.8 },
                e20: { base: 33.44, range: 1.5 },
                e85: { base: 25.44, range: 2.0 },
                premium_diesel: { base: 35.96, range: 3.0 },
            };
            const result = {};
            for (const [type, cfg] of Object.entries(types)) {
                result[type] = brands.map(brand => ({
                    brand: brand,
                    price: +(cfg.base + (Math.random() * cfg.range - cfg.range / 2)).toFixed(2),
                    change: +((Math.random() * 1.0 - 0.5)).toFixed(2),
                }));
            }
            return result;
        },

        getDemoHistory() {
            const history = [];
            const base = this.selectedType === 'diesel' ? 29.94 : 36.0;
            for (let i = 30; i >= 0; i--) {
                const d = new Date();
                d.setDate(d.getDate() - i);
                history.push({
                    date: d.toISOString().split('T')[0],
                    price: +(base + Math.sin(i / 5) * 0.8 + (Math.random() * 0.4 - 0.2)).toFixed(2),
                });
            }
            return history;
        },

        destroy() {
            if (this.refreshInterval) clearInterval(this.refreshInterval);
            if (this.chart) this.chart.destroy();
        }
    };
}
</script>
@endpush
