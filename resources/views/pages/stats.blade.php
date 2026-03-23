@extends('layouts.app')

@section('content')
<div class="min-h-screen p-4 space-y-4" x-data="statsPage()" x-init="loadStats()">
    {{-- Title --}}
    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-white flex items-center justify-center gap-2">
            <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            สถิติชุมชน ThaiHelp
        </h1>
        <p class="text-xs text-slate-400 mt-1">ข้อมูลการรายงานและการมีส่วนร่วมของชุมชน</p>
    </div>

    {{-- Loading State --}}
    <template x-if="loading">
        <div class="flex flex-col items-center justify-center py-20 gap-3">
            <div class="w-10 h-10 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-slate-400 text-sm">กำลังโหลดข้อมูลสถิติ...</p>
        </div>
    </template>

    {{-- Error State --}}
    <template x-if="error && !loading">
        <div class="text-center py-16">
            <p class="text-red-400 text-sm mb-3" x-text="error"></p>
            <button @click="loadStats()" class="metal-btn-accent px-4 py-2 rounded-lg text-sm text-white">ลองใหม่</button>
        </div>
    </template>

    <template x-if="!loading && !error && stats">
        <div class="space-y-4">
            {{-- Overview Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {{-- Total Reports --}}
                <div class="metal-panel rounded-xl p-4 text-center border border-slate-700/50">
                    <div class="text-2xl mb-1">🚨</div>
                    <div class="text-2xl font-bold text-orange-400" x-text="animNum(stats.overview.total_reports)"></div>
                    <div class="text-[10px] text-slate-400 mt-1">รายงานเหตุการณ์</div>
                </div>
                {{-- Station Reports --}}
                <div class="metal-panel rounded-xl p-4 text-center border border-slate-700/50">
                    <div class="text-2xl mb-1">⛽</div>
                    <div class="text-2xl font-bold text-emerald-400" x-text="animNum(stats.overview.total_station_reports)"></div>
                    <div class="text-[10px] text-slate-400 mt-1">รายงานปั๊มน้ำมัน</div>
                </div>
                {{-- Total Users --}}
                <div class="metal-panel rounded-xl p-4 text-center border border-slate-700/50">
                    <div class="text-2xl mb-1">👥</div>
                    <div class="text-2xl font-bold text-blue-400" x-text="animNum(stats.overview.total_users)"></div>
                    <div class="text-[10px] text-slate-400 mt-1">สมาชิกทั้งหมด</div>
                </div>
                {{-- Active Users 24h --}}
                <div class="metal-panel rounded-xl p-4 text-center border border-slate-700/50">
                    <div class="text-2xl mb-1">🟢</div>
                    <div class="text-2xl font-bold text-green-400" x-text="animNum(stats.overview.active_users_24h)"></div>
                    <div class="text-[10px] text-slate-400 mt-1">ออนไลน์ 24 ชม.</div>
                </div>
                {{-- PWA Installs --}}
                <div class="metal-panel rounded-xl p-4 text-center border border-slate-700/50">
                    <div class="text-2xl mb-1">📱</div>
                    <div class="text-2xl font-bold text-purple-400" x-text="animNum(stats.overview.pwa_installs)"></div>
                    <div class="text-[10px] text-slate-400 mt-1">ติดตั้งแอป</div>
                </div>
                {{-- Breaking News --}}
                <div class="metal-panel rounded-xl p-4 text-center border border-slate-700/50">
                    <div class="text-2xl mb-1">📰</div>
                    <div class="text-2xl font-bold text-red-400" x-text="animNum(stats.overview.breaking_news)"></div>
                    <div class="text-[10px] text-slate-400 mt-1">ข่าวด่วน</div>
                </div>
            </div>

            {{-- Total Banner --}}
            <div class="metal-panel rounded-xl p-4 border border-orange-500/30 bg-gradient-to-r from-orange-500/10 to-transparent">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-slate-400">รายงานทั้งหมดรวม</div>
                        <div class="text-3xl font-bold text-orange-400 mt-1" x-text="animNum(stats.overview.total_all)"></div>
                    </div>
                    <div class="text-4xl opacity-30">📊</div>
                </div>
            </div>

            {{-- Charts Row 1: Daily Reports + Category --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Daily Reports Line Chart --}}
                <div class="metal-panel rounded-xl p-4 border border-slate-700/50">
                    <h3 class="text-sm font-medium text-white mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2v-5z"/>
                        </svg>
                        รายงานรายวัน (14 วัน)
                    </h3>
                    <div class="relative" style="height: 220px;">
                        <canvas id="chartDaily"></canvas>
                    </div>
                </div>

                {{-- Reports by Category Doughnut --}}
                <div class="metal-panel rounded-xl p-4 border border-slate-700/50">
                    <h3 class="text-sm font-medium text-white mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                        รายงานตามประเภท
                    </h3>
                    <div class="relative" style="height: 220px;">
                        <canvas id="chartCategory"></canvas>
                    </div>
                </div>
            </div>

            {{-- Charts Row 2: Hourly Activity + Fuel Status --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Hourly Activity Bar Chart --}}
                <div class="metal-panel rounded-xl p-4 border border-slate-700/50">
                    <h3 class="text-sm font-medium text-white mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        กิจกรรมรายชั่วโมง (24 ชม.)
                    </h3>
                    <div class="relative" style="height: 220px;">
                        <canvas id="chartHourly"></canvas>
                    </div>
                </div>

                {{-- Fuel Status Doughnut --}}
                <div class="metal-panel rounded-xl p-4 border border-slate-700/50">
                    <h3 class="text-sm font-medium text-white mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                        สถานะน้ำมัน
                    </h3>
                    <div class="relative" style="height: 220px;">
                        <canvas id="chartFuel"></canvas>
                    </div>
                </div>
            </div>

            {{-- Leaderboard --}}
            <div class="metal-panel rounded-xl p-4 border border-slate-700/50">
                <h3 class="text-sm font-medium text-white mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    อันดับผู้รายงาน Top 10
                </h3>

                <template x-if="stats.top_reporters && stats.top_reporters.length > 0">
                    <div class="overflow-x-auto -mx-4 px-4">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-700/50">
                                    <th class="text-left py-2 pl-2">#</th>
                                    <th class="text-left py-2">สมาชิก</th>
                                    <th class="text-center py-2">ระดับ</th>
                                    <th class="text-center py-2">รายงาน</th>
                                    <th class="text-center py-2">ยืนยัน</th>
                                    <th class="text-right py-2 pr-2">คะแนน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(reporter, idx) in stats.top_reporters" :key="idx">
                                    <tr class="border-b border-slate-700/30 hover:bg-slate-800/30 transition-colors">
                                        <td class="py-2.5 pl-2">
                                            <span x-show="idx === 0" class="text-lg">🥇</span>
                                            <span x-show="idx === 1" class="text-lg">🥈</span>
                                            <span x-show="idx === 2" class="text-lg">🥉</span>
                                            <span x-show="idx > 2" class="text-slate-500" x-text="idx + 1"></span>
                                        </td>
                                        <td class="py-2.5">
                                            <div class="flex items-center gap-2">
                                                <img :src="reporter.avatar_url || '/images/default-avatar.webp'"
                                                     class="w-7 h-7 rounded-full border border-slate-600 object-cover"
                                                     onerror="this.src='/images/default-avatar.webp'"
                                                     alt="">
                                                <span class="text-white font-medium truncate max-w-[100px]"
                                                      x-text="reporter.nickname || reporter.name || 'ไม่ระบุ'"></span>
                                            </div>
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <span x-text="getStars(reporter.reputation_score)"></span>
                                        </td>
                                        <td class="py-2.5 text-center text-orange-400 font-medium" x-text="reporter.total_reports"></td>
                                        <td class="py-2.5 text-center text-blue-400" x-text="reporter.total_confirmations"></td>
                                        <td class="py-2.5 pr-2 text-right">
                                            <span class="text-yellow-400 font-bold" x-text="reporter.reputation_score.toLocaleString()"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                <template x-if="!stats.top_reporters || stats.top_reporters.length === 0">
                    <div class="text-center py-6 text-slate-500 text-xs">ยังไม่มีข้อมูลผู้รายงาน</div>
                </template>
            </div>

            {{-- Last Updated --}}
            <div class="text-center text-[10px] text-slate-600 pb-4">
                อัปเดตล่าสุด: <span x-text="lastUpdated"></span> | รีเฟรชอัตโนมัติทุก 5 นาที
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function statsPage() {
    return {
        stats: null,
        loading: true,
        error: null,
        lastUpdated: '',
        charts: {},
        refreshInterval: null,

        async loadStats() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('/api/stats');
                if (!res.ok) throw new Error('ไม่สามารถโหลดข้อมูลได้');
                const json = await res.json();
                if (!json.success) throw new Error('เซิร์ฟเวอร์ตอบกลับผิดพลาด');
                this.stats = json.data;
                this.lastUpdated = new Date().toLocaleTimeString('th-TH');
                this.loading = false;

                // Render charts after DOM updates
                this.$nextTick(() => {
                    this.renderCharts();
                });

                // Auto refresh every 5 minutes
                if (!this.refreshInterval) {
                    this.refreshInterval = setInterval(() => this.refreshStats(), 300000);
                }
            } catch (e) {
                this.error = e.message || 'เกิดข้อผิดพลาด';
                this.loading = false;
            }
        },

        async refreshStats() {
            try {
                const res = await fetch('/api/stats');
                if (!res.ok) return;
                const json = await res.json();
                if (!json.success) return;
                this.stats = json.data;
                this.lastUpdated = new Date().toLocaleTimeString('th-TH');
                this.renderCharts();
            } catch (e) {
                // Silent fail on refresh
            }
        },

        animNum(n) {
            return (n || 0).toLocaleString('th-TH');
        },

        getStars(score) {
            if (score >= 500) return '\u2B50\u2B50\u2B50\u2B50\u2B50';
            if (score >= 101) return '\u2B50\u2B50\u2B50\u2B50';
            if (score >= 51) return '\u2B50\u2B50\u2B50';
            if (score >= 11) return '\u2B50\u2B50';
            return '\u2B50';
        },

        renderCharts() {
            // Destroy existing charts
            Object.values(this.charts).forEach(c => { if (c) c.destroy(); });
            this.charts = {};

            const baseFont = { family: "'Noto Sans Thai', sans-serif", size: 11 };
            const gridColor = 'rgba(148, 163, 184, 0.08)';
            const tickColor = 'rgba(148, 163, 184, 0.5)';

            Chart.defaults.font = baseFont;
            Chart.defaults.color = tickColor;

            // ═══ Daily Reports Line Chart ═══
            const dailyCtx = document.getElementById('chartDaily');
            if (dailyCtx) {
                const ctx = dailyCtx.getContext('2d');
                const gradientOrange = ctx.createLinearGradient(0, 0, 0, 200);
                gradientOrange.addColorStop(0, 'rgba(249, 115, 22, 0.3)');
                gradientOrange.addColorStop(1, 'rgba(249, 115, 22, 0.0)');

                const gradientEmerald = ctx.createLinearGradient(0, 0, 0, 200);
                gradientEmerald.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
                gradientEmerald.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

                this.charts.daily = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.stats.daily_reports.map(d => d.date),
                        datasets: [
                            {
                                label: 'เหตุการณ์',
                                data: this.stats.daily_reports.map(d => d.incidents),
                                borderColor: '#f97316',
                                backgroundColor: gradientOrange,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointBackgroundColor: '#f97316',
                                pointBorderColor: '#1e293b',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'ปั๊มน้ำมัน',
                                data: this.stats.daily_reports.map(d => d.stations),
                                borderColor: '#10b981',
                                backgroundColor: gradientEmerald,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#1e293b',
                                pointBorderWidth: 2,
                                pointHoverRadius: 6,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { boxWidth: 12, padding: 12, usePointStyle: true, pointStyle: 'circle' },
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#f1f5f9',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(249, 115, 22, 0.3)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                padding: 10,
                                callbacks: {
                                    label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y} รายงาน`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor },
                                ticks: { color: tickColor, maxRotation: 45 },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: tickColor, precision: 0 },
                            },
                        },
                        animation: {
                            duration: 1200,
                            easing: 'easeOutQuart',
                        },
                    },
                });
            }

            // ═══ Reports by Category Doughnut ═══
            const catCtx = document.getElementById('chartCategory');
            if (catCtx) {
                const catLabels = {
                    'accident': 'อุบัติเหตุ',
                    'flood': 'น้ำท่วม',
                    'roadblock': 'ถนนปิด',
                    'checkpoint': 'จุดตรวจ',
                    'construction': 'ก่อสร้าง',
                    'other': 'อื่นๆ',
                };
                const catColors = {
                    'accident': '#ef4444',
                    'flood': '#3b82f6',
                    'roadblock': '#f97316',
                    'checkpoint': '#a855f7',
                    'construction': '#eab308',
                    'other': '#6b7280',
                };
                const cats = Object.keys(this.stats.reports_by_category);
                const hasData = cats.length > 0;

                this.charts.category = new Chart(catCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: hasData ? cats.map(c => catLabels[c] || c) : ['ยังไม่มีข้อมูล'],
                        datasets: [{
                            data: hasData ? cats.map(c => this.stats.reports_by_category[c]) : [1],
                            backgroundColor: hasData ? cats.map(c => catColors[c] || '#6b7280') : ['#334155'],
                            borderColor: '#0f172a',
                            borderWidth: 3,
                            hoverOffset: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '55%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { boxWidth: 10, padding: 8, usePointStyle: true, pointStyle: 'rectRounded' },
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#f1f5f9',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(249, 115, 22, 0.3)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.parsed} รายงาน`,
                                },
                            },
                        },
                        animation: {
                            animateRotate: true,
                            duration: 1000,
                            easing: 'easeOutQuart',
                        },
                    },
                });
            }

            // ═══ Hourly Activity Bar Chart ═══
            const hourlyCtx = document.getElementById('chartHourly');
            if (hourlyCtx) {
                const ctx = hourlyCtx.getContext('2d');
                const gradientBar = ctx.createLinearGradient(0, 200, 0, 0);
                gradientBar.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
                gradientBar.addColorStop(1, 'rgba(139, 92, 246, 0.8)');

                this.charts.hourly = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: this.stats.hourly_activity.map(h => h.hour),
                        datasets: [{
                            label: 'รายงาน',
                            data: this.stats.hourly_activity.map(h => h.count),
                            backgroundColor: gradientBar,
                            borderColor: 'rgba(139, 92, 246, 0.6)',
                            borderWidth: 1,
                            borderRadius: 4,
                            borderSkipped: false,
                            hoverBackgroundColor: 'rgba(139, 92, 246, 0.9)',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#f1f5f9',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(139, 92, 246, 0.3)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                callbacks: {
                                    title: ctx => `เวลา ${ctx[0].label}`,
                                    label: ctx => `${ctx.parsed.y} รายงาน`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: tickColor,
                                    maxRotation: 0,
                                    callback: function(val, idx) {
                                        // Show every 3rd label on mobile
                                        return idx % 3 === 0 ? this.getLabelForValue(val) : '';
                                    },
                                },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { color: tickColor, precision: 0 },
                            },
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart',
                            delay: (ctx) => ctx.dataIndex * 30,
                        },
                    },
                });
            }

            // ═══ Fuel Status Doughnut ═══
            const fuelCtx = document.getElementById('chartFuel');
            if (fuelCtx) {
                const fuelLabels = {
                    'available': 'มีน้ำมัน',
                    'low': 'เหลือน้อย',
                    'empty': 'หมด',
                    'unknown': 'ไม่ทราบ',
                };
                const fuelColors = {
                    'available': '#10b981',
                    'low': '#eab308',
                    'empty': '#ef4444',
                    'unknown': '#6b7280',
                };
                const fuels = Object.keys(this.stats.fuel_stats);
                const hasFuelData = fuels.length > 0;

                this.charts.fuel = new Chart(fuelCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: hasFuelData ? fuels.map(f => fuelLabels[f] || f) : ['ยังไม่มีข้อมูล'],
                        datasets: [{
                            data: hasFuelData ? fuels.map(f => this.stats.fuel_stats[f]) : [1],
                            backgroundColor: hasFuelData ? fuels.map(f => fuelColors[f] || '#6b7280') : ['#334155'],
                            borderColor: '#0f172a',
                            borderWidth: 3,
                            hoverOffset: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '55%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { boxWidth: 10, padding: 8, usePointStyle: true, pointStyle: 'rectRounded' },
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                                titleColor: '#f1f5f9',
                                bodyColor: '#cbd5e1',
                                borderColor: 'rgba(16, 185, 129, 0.3)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                callbacks: {
                                    label: ctx => `${ctx.label}: ${ctx.parsed} รายการ`,
                                },
                            },
                        },
                        animation: {
                            animateRotate: true,
                            duration: 1000,
                            easing: 'easeOutQuart',
                        },
                    },
                });
            }
        },
    };
}
</script>
@endpush
