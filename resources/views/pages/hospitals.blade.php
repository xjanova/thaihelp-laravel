@extends('layouts.app')

@section('content')
<div class="min-h-screen p-4 space-y-4" x-data="hospitalsPage()" x-init="init()">
    {{-- Title --}}
    <div class="text-center mb-6">
        <h1 class="text-xl font-bold text-white flex items-center justify-center gap-2">
            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            โรงพยาบาลใกล้ฉัน
        </h1>
        <p class="text-xs text-slate-400 mt-1">สถานะเตียง ER และข้อมูลโรงพยาบาลแบบเรียลไทม์</p>
    </div>

    {{-- GPS Status --}}
    <div class="text-center text-xs text-slate-500 mb-2" x-show="!locationReady && !locationError">
        <div class="flex items-center justify-center gap-2">
            <div class="w-4 h-4 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
            กำลังค้นหาตำแหน่งของคุณ...
        </div>
    </div>
    <div class="text-center text-xs text-red-400 mb-2" x-show="locationError" x-text="locationError"></div>

    {{-- Loading State --}}
    <template x-if="loading">
        <div class="flex flex-col items-center justify-center py-20 gap-3">
            <div class="w-10 h-10 border-4 border-red-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-slate-400 text-sm">กำลังค้นหาโรงพยาบาลใกล้เคียง...</p>
        </div>
    </template>

    {{-- Error State --}}
    <template x-if="error && !loading">
        <div class="text-center py-16">
            <p class="text-red-400 text-sm mb-3" x-text="error"></p>
            <button @click="fetchHospitals()" class="metal-btn-accent px-4 py-2 rounded-lg text-sm text-white">ลองใหม่</button>
        </div>
    </template>

    {{-- Main Content --}}
    <template x-if="!loading && !error">
        <div class="space-y-4">
            {{-- Last Updated --}}
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span x-show="lastUpdated">อัพเดทล่าสุด: <span x-text="lastUpdated"></span></span>
                <button @click="fetchHospitals()" class="flex items-center gap-1 text-orange-400 hover:text-orange-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    รีเฟรช
                </button>
            </div>

            {{-- ER Status Legend --}}
            <div class="metal-panel rounded-lg p-3">
                <div class="flex flex-wrap items-center justify-center gap-3 text-xs text-slate-400">
                    <span class="flex items-center gap-1">🟢 เปิดปกติ</span>
                    <span class="flex items-center gap-1">🟡 คนเยอะ</span>
                    <span class="flex items-center gap-1">🔴 เต็ม</span>
                    <span class="flex items-center gap-1">⚪ ปิด</span>
                </div>
            </div>

            {{-- Hospital List --}}
            <div class="space-y-3" x-show="hospitals.length > 0">
                <template x-for="h in hospitals" :key="h.id || h.hospital_name">
                    <div class="metal-panel rounded-xl p-4 space-y-3">
                        {{-- Header: Name + Type + ER --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-white truncate" x-text="h.hospital_name"></h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-medium"
                                          :class="typeClass(h.hospital_type)"
                                          x-text="typeLabel(h.hospital_type)"></span>
                                    <span class="text-xs text-slate-400" x-show="h.distance" x-text="h.distance + ' กม.'"></span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-lg" x-text="erEmoji(h.er_status)" :title="erLabel(h.er_status)"></div>
                        </div>

                        {{-- ER Status Bar --}}
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium w-8" :class="erColor(h.er_status)">ER</span>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="erBadgeClass(h.er_status)"
                                  x-text="erLabel(h.er_status)"></span>
                        </div>

                        {{-- Bed Counts --}}
                        <div class="space-y-2" x-show="h.total_beds">
                            {{-- General Beds --}}
                            <div>
                                <div class="flex justify-between text-xs text-slate-400 mb-1">
                                    <span>เตียงทั่วไป</span>
                                    <span><span class="text-green-400 font-bold" x-text="h.available_beds ?? '?'"></span> / <span x-text="h.total_beds"></span> ว่าง</span>
                                </div>
                                <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :class="bedBarColor(h.available_beds, h.total_beds)"
                                         :style="'width:' + bedPercent(h.available_beds, h.total_beds) + '%'"></div>
                                </div>
                            </div>
                            {{-- ICU Beds --}}
                            <div x-show="h.icu_beds">
                                <div class="flex justify-between text-xs text-slate-400 mb-1">
                                    <span>ICU</span>
                                    <span><span class="text-blue-400 font-bold" x-text="h.icu_available ?? '?'"></span> / <span x-text="h.icu_beds"></span> ว่าง</span>
                                </div>
                                <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full transition-all duration-500"
                                         :style="'width:' + bedPercent(h.icu_available, h.icu_beds) + '%'"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Updated time --}}
                        <div class="text-[10px] text-slate-500" x-show="h.updated_at" x-text="'รายงานเมื่อ ' + timeAgo(h.updated_at)"></div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2 pt-1">
                            <a :href="'https://www.google.com/maps/dir/?api=1&destination=' + h.latitude + ',' + h.longitude"
                               target="_blank" rel="noopener"
                               class="flex-1 text-center text-xs py-2 rounded-lg bg-blue-600/20 text-blue-400 hover:bg-blue-600/30 transition font-medium">
                                🧭 นำทาง
                            </a>
                            <a :href="'tel:' + h.phone" x-show="h.phone"
                               class="flex-1 text-center text-xs py-2 rounded-lg bg-green-600/20 text-green-400 hover:bg-green-600/30 transition font-medium">
                                📞 โทร
                            </a>
                            <button @click="openReport(h)"
                                    class="flex-1 text-center text-xs py-2 rounded-lg bg-orange-600/20 text-orange-400 hover:bg-orange-600/30 transition font-medium">
                                📝 รายงาน
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty State --}}
            <div class="text-center py-16" x-show="hospitals.length === 0 && locationReady">
                <p class="text-slate-400 text-sm mb-2">ไม่พบข้อมูลโรงพยาบาลใกล้เคียง</p>
                <p class="text-slate-500 text-xs">เป็นคนแรกที่รายงานสถานะโรงพยาบาล!</p>
                <button @click="showReportForm = true" class="mt-4 metal-btn-accent px-4 py-2 rounded-lg text-sm text-white">
                    + รายงานโรงพยาบาล
                </button>
            </div>

            {{-- Report Button (floating) --}}
            <div class="fixed bottom-20 right-4 z-40" x-show="!showReportForm">
                <button @click="showReportForm = true"
                        class="w-14 h-14 rounded-full bg-orange-600 text-white shadow-lg shadow-orange-600/30 flex items-center justify-center text-2xl hover:bg-orange-500 transition">
                    +
                </button>
            </div>

            {{-- Report Form --}}
            <div x-show="showReportForm" x-transition class="metal-panel rounded-xl p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-white">📝 รายงานสถานะโรงพยาบาล</h2>
                    <button @click="showReportForm = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                {{-- Hospital Select --}}
                <div>
                    <label class="text-xs text-slate-400 block mb-1">โรงพยาบาล</label>
                    <select x-model="report.hospital_name"
                            class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none">
                        <option value="">-- เลือกจากรายการ หรือพิมพ์ด้านล่าง --</option>
                        <template x-for="h in hospitals" :key="h.hospital_name">
                            <option :value="h.hospital_name" x-text="h.hospital_name"></option>
                        </template>
                    </select>
                    <input type="text" x-model="report.hospital_name_custom"
                           placeholder="หรือพิมพ์ชื่อโรงพยาบาล..."
                           class="w-full mt-2 bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-orange-500 focus:outline-none">
                </div>

                {{-- ER Status --}}
                <div>
                    <label class="text-xs text-slate-400 block mb-2">สถานะ ER</label>
                    <div class="grid grid-cols-4 gap-2">
                        <template x-for="s in erStatuses" :key="s.value">
                            <button @click="report.er_status = s.value"
                                    class="text-center py-2 rounded-lg border text-xs transition"
                                    :class="report.er_status === s.value
                                        ? 'border-orange-500 bg-orange-600/20 text-white'
                                        : 'border-slate-600 bg-slate-800 text-slate-400 hover:border-slate-500'">
                                <span class="block text-lg" x-text="s.emoji"></span>
                                <span x-text="s.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Bed Counts --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-400 block mb-1">เตียงทั้งหมด</label>
                        <input type="number" x-model.number="report.total_beds" min="0" placeholder="0"
                               class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 block mb-1">เตียงว่าง</label>
                        <input type="number" x-model.number="report.available_beds" min="0" placeholder="0"
                               class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 block mb-1">ICU ทั้งหมด</label>
                        <input type="number" x-model.number="report.icu_beds" min="0" placeholder="0"
                               class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 block mb-1">ICU ว่าง</label>
                        <input type="number" x-model.number="report.icu_available" min="0" placeholder="0"
                               class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white focus:border-orange-500 focus:outline-none">
                    </div>
                </div>

                {{-- Note --}}
                <div>
                    <label class="text-xs text-slate-400 block mb-1">หมายเหตุ</label>
                    <textarea x-model="report.note" rows="2" placeholder="เช่น รอนาน 2 ชม., ER ไม่รับเคสเล็ก..."
                              class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-orange-500 focus:outline-none resize-none"></textarea>
                </div>

                {{-- Submit --}}
                <button @click="submitReport()"
                        :disabled="submitting"
                        class="w-full py-3 rounded-lg font-bold text-sm transition"
                        :class="submitting ? 'bg-slate-600 text-slate-400 cursor-not-allowed' : 'bg-orange-600 text-white hover:bg-orange-500'">
                    <span x-show="!submitting">ส่งรายงาน</span>
                    <span x-show="submitting" class="flex items-center justify-center gap-2">
                        <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        กำลังส่ง...
                    </span>
                </button>
                <p class="text-xs text-center text-green-400" x-show="submitSuccess" x-transition>ส่งรายงานสำเร็จ! ขอบคุณค่ะ 🙏</p>
                <p class="text-xs text-center text-red-400" x-show="submitError" x-text="submitError"></p>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function hospitalsPage() {
    return {
        hospitals: [],
        loading: false,
        error: null,
        locationReady: false,
        locationError: null,
        lat: null,
        lng: null,
        lastUpdated: null,
        showReportForm: false,
        submitting: false,
        submitSuccess: false,
        submitError: null,
        refreshInterval: null,

        report: {
            hospital_name: '',
            hospital_name_custom: '',
            er_status: 'open',
            total_beds: null,
            available_beds: null,
            icu_beds: null,
            icu_available: null,
            note: '',
        },

        erStatuses: [
            { value: 'open', emoji: '🟢', label: 'เปิดปกติ' },
            { value: 'busy', emoji: '🟡', label: 'คนเยอะ' },
            { value: 'full', emoji: '🔴', label: 'เต็ม' },
            { value: 'closed', emoji: '⚪', label: 'ปิด' },
        ],

        init() {
            this.detectLocation();
            // Auto-refresh every 10 minutes
            this.refreshInterval = setInterval(() => {
                if (this.locationReady) this.fetchHospitals();
            }, 10 * 60 * 1000);
        },

        detectLocation() {
            if (!navigator.geolocation) {
                this.locationError = 'เบราว์เซอร์ไม่รองรับ GPS กรุณาเปิดตำแหน่ง';
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.locationReady = true;
                    this.locationError = null;
                    this.fetchHospitals();
                },
                (err) => {
                    this.locationError = 'ไม่สามารถเข้าถึงตำแหน่ง: ' + err.message;
                    // Fallback: Bangkok center
                    this.lat = 13.7563;
                    this.lng = 100.5018;
                    this.locationReady = true;
                    this.fetchHospitals();
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        async fetchHospitals() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(`/api/hospitals?lat=${this.lat}&lng=${this.lng}`);
                if (!res.ok) throw new Error('ไม่สามารถโหลดข้อมูลได้');
                const data = await res.json();
                this.hospitals = data.data || data || [];
                this.lastUpdated = new Date().toLocaleTimeString('th-TH');
            } catch (e) {
                this.error = e.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
            } finally {
                this.loading = false;
            }
        },

        async submitReport() {
            const name = this.report.hospital_name_custom || this.report.hospital_name;
            if (!name) {
                this.submitError = 'กรุณาเลือกหรือพิมพ์ชื่อโรงพยาบาล';
                return;
            }
            this.submitting = true;
            this.submitSuccess = false;
            this.submitError = null;
            try {
                const payload = {
                    hospital_name: name,
                    er_status: this.report.er_status,
                    total_beds: this.report.total_beds,
                    available_beds: this.report.available_beds,
                    icu_beds: this.report.icu_beds,
                    icu_available: this.report.icu_available,
                    note: this.report.note,
                    latitude: this.lat,
                    longitude: this.lng,
                };
                const res = await fetch('/api/hospitals', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error('ส่งรายงานไม่สำเร็จ');
                this.submitSuccess = true;
                this.showReportForm = false;
                this.resetReport();
                this.fetchHospitals();
            } catch (e) {
                this.submitError = e.message || 'เกิดข้อผิดพลาด';
            } finally {
                this.submitting = false;
            }
        },

        resetReport() {
            this.report = {
                hospital_name: '', hospital_name_custom: '', er_status: 'open',
                total_beds: null, available_beds: null, icu_beds: null, icu_available: null, note: '',
            };
        },

        openReport(h) {
            this.report.hospital_name = h.hospital_name;
            this.report.hospital_name_custom = '';
            this.report.er_status = h.er_status || 'open';
            this.report.total_beds = h.total_beds;
            this.report.available_beds = h.available_beds;
            this.report.icu_beds = h.icu_beds;
            this.report.icu_available = h.icu_available;
            this.showReportForm = true;
        },

        // Helpers
        erEmoji(status) {
            return { open: '🟢', busy: '🟡', full: '🔴', closed: '⚪' }[status] || '⚪';
        },
        erLabel(status) {
            return { open: 'เปิดปกติ', busy: 'คนเยอะ', full: 'เต็ม', closed: 'ปิด' }[status] || 'ไม่ทราบ';
        },
        erColor(status) {
            return { open: 'text-green-400', busy: 'text-yellow-400', full: 'text-red-400', closed: 'text-slate-400' }[status] || 'text-slate-400';
        },
        erBadgeClass(status) {
            return {
                open: 'bg-green-600/20 text-green-400',
                busy: 'bg-yellow-600/20 text-yellow-400',
                full: 'bg-red-600/20 text-red-400',
                closed: 'bg-slate-600/20 text-slate-400',
            }[status] || 'bg-slate-600/20 text-slate-400';
        },
        typeLabel(type) {
            return { general: 'รพ.ทั่วไป', community: 'รพ.ชุมชน', private: 'รพ.เอกชน', clinic: 'คลินิก' }[type] || type;
        },
        typeClass(type) {
            return {
                general: 'bg-blue-600/20 text-blue-400',
                community: 'bg-teal-600/20 text-teal-400',
                private: 'bg-purple-600/20 text-purple-400',
                clinic: 'bg-slate-600/20 text-slate-400',
            }[type] || 'bg-slate-600/20 text-slate-400';
        },
        bedPercent(available, total) {
            if (!total || total <= 0) return 0;
            return Math.max(0, Math.min(100, Math.round(((available || 0) / total) * 100)));
        },
        bedBarColor(available, total) {
            const pct = this.bedPercent(available, total);
            if (pct > 50) return 'bg-green-500';
            if (pct > 20) return 'bg-yellow-500';
            return 'bg-red-500';
        },
        timeAgo(dateStr) {
            if (!dateStr) return '';
            const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 60000);
            if (diff < 1) return 'เมื่อสักครู่';
            if (diff < 60) return diff + ' นาทีที่แล้ว';
            if (diff < 1440) return Math.floor(diff / 60) + ' ชม.ที่แล้ว';
            return Math.floor(diff / 1440) + ' วันที่แล้ว';
        },
    };
}
</script>
@endpush
@endsection
