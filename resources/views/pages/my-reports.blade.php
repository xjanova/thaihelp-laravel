@extends('layouts.app')

@section('content')
<div class="min-h-screen px-4 py-4" x-data="myReportsPage()">

    {{-- Profile Card --}}
    <div class="metal-panel rounded-2xl p-5 mb-5">
        <div class="flex items-center gap-4">
            {{-- Avatar --}}
            <div class="relative flex-shrink-0">
                <template x-if="profile.avatar">
                    <img :src="profile.avatar" class="w-16 h-16 rounded-full border-2 border-orange-500/50 object-cover">
                </template>
                <template x-if="!profile.avatar">
                    <div class="w-16 h-16 rounded-full border-2 border-orange-500/50 bg-gradient-to-br from-orange-600 to-orange-800 flex items-center justify-center">
                        <span class="text-2xl font-bold text-white" x-text="(profile.nickname || 'U').charAt(0).toUpperCase()"></span>
                    </div>
                </template>
                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-green-500 border-2 border-slate-900"></div>
            </div>

            {{-- User Info --}}
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-slate-100 truncate" x-text="profile.nickname || 'ผู้ใช้'"></h2>

                {{-- Star Level --}}
                <div class="flex items-center gap-1.5 mt-1">
                    <span class="text-xs text-slate-400" x-text="'Lv.' + (profile.star_level || 1)"></span>
                    <div class="flex items-center">
                        <template x-for="i in 5" :key="i">
                            <svg class="w-3.5 h-3.5" :class="i <= (profile.star_level || 0) ? 'text-yellow-400' : 'text-slate-600'" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </template>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="flex items-center gap-3 mt-2">
                    <div class="flex items-center gap-1">
                        <span class="text-orange-400 text-xs font-semibold" x-text="profile.reputation ?? 0"></span>
                        <span class="text-[10px] text-slate-500">คะแนน</span>
                    </div>
                    <div class="w-px h-3 bg-slate-700"></div>
                    <div class="flex items-center gap-1">
                        <span class="text-blue-400 text-xs font-semibold" x-text="profile.reports_count ?? 0"></span>
                        <span class="text-[10px] text-slate-500">รายงาน</span>
                    </div>
                    <div class="w-px h-3 bg-slate-700"></div>
                    <div class="flex items-center gap-1">
                        <span class="text-green-400 text-xs font-semibold" x-text="profile.confirmations_count ?? 0"></span>
                        <span class="text-[10px] text-slate-500">ยืนยัน</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Member Since --}}
        <div class="mt-3 pt-3 border-t border-slate-700/50 flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-[11px] text-slate-500">สมาชิกตั้งแต่ <span x-text="formatDate(profile.created_at)"></span></span>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex gap-2 mb-4">
        <button @click="activeFilter = 'all'" :class="activeFilter === 'all' ? 'metal-btn-accent text-white' : 'metal-btn text-slate-300'"
                class="px-4 py-2 rounded-xl text-xs font-medium transition-all">
            ทั้งหมด
            <span class="ml-1 opacity-70" x-text="'(' + reports.length + ')'"></span>
        </button>
        <button @click="activeFilter = 'incident'" :class="activeFilter === 'incident' ? 'metal-btn-accent text-white' : 'metal-btn text-slate-300'"
                class="px-4 py-2 rounded-xl text-xs font-medium transition-all">
            เหตุการณ์
            <span class="ml-1 opacity-70" x-text="'(' + incidentCount + ')'"></span>
        </button>
        <button @click="activeFilter = 'station'" :class="activeFilter === 'station' ? 'metal-btn-accent text-white' : 'metal-btn text-slate-300'"
                class="px-4 py-2 rounded-xl text-xs font-medium transition-all">
            ปั๊มน้ำมัน
            <span class="ml-1 opacity-70" x-text="'(' + stationCount + ')'"></span>
        </button>
    </div>

    {{-- Loading State --}}
    <template x-if="loading">
        <div class="flex flex-col items-center justify-center py-16">
            <div class="w-10 h-10 border-3 border-slate-600 border-t-orange-500 rounded-full animate-spin mb-4"></div>
            <p class="text-sm text-slate-500">กำลังโหลดรายงาน...</p>
        </div>
    </template>

    {{-- Report List --}}
    <template x-if="!loading">
        <div>
            {{-- Empty State --}}
            <template x-if="filteredReports.length === 0">
                <div class="metal-panel rounded-2xl p-8 text-center">
                    <div class="text-5xl mb-4 opacity-50">📝</div>
                    <h3 class="text-base font-semibold text-slate-300 mb-2">ยังไม่มีรายงาน</h3>
                    <p class="text-xs text-slate-500 mb-5">เริ่มช่วยเหลือชุมชนด้วยการรายงานเหตุการณ์</p>
                    <a href="/report" class="metal-btn-accent inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        แจ้งเหตุใหม่
                    </a>
                </div>
            </template>

            {{-- Report Cards --}}
            <div class="space-y-3">
                <template x-for="report in filteredReports" :key="report.id">
                    <div class="metal-panel metal-panel-hover rounded-xl overflow-hidden"
                         :class="editingId === report.id ? 'ring-1 ring-orange-500/40' : ''">

                        {{-- View Mode --}}
                        <div x-show="editingId !== report.id" class="p-4">
                            <div class="flex items-start gap-3">
                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Category Badge + Status --}}
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <template x-if="report.type === 'incident'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium"
                                                  :class="getCategoryStyle(report.category)">
                                                <span x-text="getCategoryEmoji(report.category)"></span>
                                                <span x-text="getCategoryLabel(report.category)"></span>
                                            </span>
                                        </template>
                                        <template x-if="report.type === 'station'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-500/15 text-blue-400 border border-blue-500/20">
                                                ⛽ ปั๊มน้ำมัน
                                            </span>
                                        </template>

                                        {{-- Status Badge --}}
                                        <template x-if="report.status === 'active'">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-green-500/15 text-green-400 border border-green-500/20">ใช้งาน</span>
                                        </template>
                                        <template x-if="report.status === 'expired'">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-500/15 text-slate-400 border border-slate-500/20">หมดอายุ</span>
                                        </template>
                                        <template x-if="report.verified">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 flex items-center gap-0.5">
                                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                ยืนยันแล้ว
                                            </span>
                                        </template>
                                    </div>

                                    {{-- Title --}}
                                    <h3 class="text-sm font-semibold text-slate-200 mb-1 line-clamp-1" x-text="report.title || report.station_name || 'ไม่มีชื่อ'"></h3>

                                    {{-- Description Preview --}}
                                    <template x-if="report.description">
                                        <p class="text-xs text-slate-400 line-clamp-2 mb-2" x-text="report.description"></p>
                                    </template>

                                    {{-- Fuel Count (for stations) --}}
                                    <template x-if="report.type === 'station' && report.fuel_count !== undefined">
                                        <p class="text-xs text-slate-400 mb-2">
                                            น้ำมัน: <span class="text-blue-400 font-medium" x-text="report.fuel_count + ' รายการ'"></span>
                                        </p>
                                    </template>

                                    {{-- Meta Row --}}
                                    <div class="flex items-center gap-3 text-[10px] text-slate-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span x-text="timeAgo(report.created_at)"></span>
                                        </span>
                                        <template x-if="report.upvotes !== undefined">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                                                </svg>
                                                <span x-text="report.upvotes"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex flex-col gap-1.5 flex-shrink-0">
                                    <button @click="startEdit(report)" class="metal-btn p-2 rounded-lg group" title="แก้ไข">
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-orange-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="confirmDelete(report)" class="metal-btn p-2 rounded-lg group" title="ลบ">
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-red-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Edit Mode --}}
                        <div x-show="editingId === report.id" x-transition class="p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span class="text-sm font-semibold text-orange-400">แก้ไขรายงาน</span>
                            </div>

                            {{-- Editable Title --}}
                            <div class="mb-3">
                                <label class="block text-[10px] text-slate-500 mb-1">หัวข้อ</label>
                                <input type="text" x-model="editForm.title"
                                       class="w-full px-3 py-2 rounded-lg bg-slate-800/80 border border-slate-600/50 text-sm text-slate-200 placeholder-slate-500 outline-none focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/20 transition-all">
                            </div>

                            {{-- Editable Description --}}
                            <div class="mb-3">
                                <label class="block text-[10px] text-slate-500 mb-1">รายละเอียด</label>
                                <textarea x-model="editForm.description" rows="3"
                                          class="w-full px-3 py-2 rounded-lg bg-slate-800/80 border border-slate-600/50 text-sm text-slate-200 placeholder-slate-500 outline-none focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/20 resize-none transition-all"></textarea>
                            </div>

                            {{-- Editable Category (incidents only) --}}
                            <template x-if="report.type === 'incident'">
                                <div class="mb-4">
                                    <label class="block text-[10px] text-slate-500 mb-1">ประเภท</label>
                                    <select x-model="editForm.category"
                                            class="w-full px-3 py-2 rounded-lg bg-slate-800/80 border border-slate-600/50 text-sm text-slate-200 outline-none focus:border-orange-500/50 transition-all">
                                        <option value="accident">🚗 อุบัติเหตุ</option>
                                        <option value="flood">🌊 น้ำท่วม</option>
                                        <option value="roadblock">🚧 ถนนปิด</option>
                                        <option value="checkpoint">👮 จุดตรวจ</option>
                                        <option value="construction">🏗️ ก่อสร้าง</option>
                                        <option value="other">⚠️ อื่นๆ</option>
                                    </select>
                                </div>
                            </template>

                            {{-- Edit Error --}}
                            <template x-if="editError">
                                <div class="mb-3 p-2 rounded-lg bg-red-500/10 border border-red-500/20">
                                    <p class="text-xs text-red-400" x-text="editError"></p>
                                </div>
                            </template>

                            {{-- Edit Actions --}}
                            <div class="flex items-center gap-2">
                                <button @click="saveEdit(report)" :disabled="saving"
                                        class="metal-btn-accent px-4 py-2 rounded-lg text-xs font-semibold text-white flex items-center gap-1.5">
                                    <template x-if="!saving">
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            บันทึก
                                        </span>
                                    </template>
                                    <template x-if="saving">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                            กำลังบันทึก...
                                        </span>
                                    </template>
                                </button>
                                <button @click="cancelEdit()" class="metal-btn px-4 py-2 rounded-lg text-xs text-slate-300">
                                    ยกเลิก
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm px-4">
        <div x-show="showDeleteModal" x-transition.scale.90 @click.away="showDeleteModal = false"
             class="metal-panel rounded-2xl p-6 max-w-sm w-full">
            <div class="text-center mb-4">
                <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-100 mb-1">ลบรายงานนี้?</h3>
                <p class="text-xs text-slate-400">การลบจะไม่สามารถกู้คืนได้</p>
            </div>
            <div class="flex gap-2">
                <button @click="showDeleteModal = false" class="metal-btn flex-1 py-2.5 rounded-xl text-xs font-medium text-slate-300">
                    ยกเลิก
                </button>
                <button @click="executeDelete()" :disabled="deleting"
                        class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-white bg-gradient-to-b from-red-500 to-red-700 border border-red-400/20 shadow-lg shadow-red-500/20 hover:from-red-400 hover:to-red-600 transition-all">
                    <template x-if="!deleting">
                        <span>ลบรายงาน</span>
                    </template>
                    <template x-if="deleting">
                        <span class="flex items-center justify-center gap-1.5">
                            <span class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            กำลังลบ...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div x-show="toast.show" x-transition.opacity
         class="fixed top-20 left-1/2 -translate-x-1/2 z-50 max-w-xs w-full px-4">
        <div class="metal-panel rounded-xl px-4 py-3 flex items-center gap-3 shadow-2xl"
             :class="toast.type === 'success' ? 'border-green-500/30' : 'border-red-500/30'">
            <template x-if="toast.type === 'success'">
                <div class="w-8 h-8 rounded-full bg-green-500/15 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </template>
            <template x-if="toast.type === 'error'">
                <div class="w-8 h-8 rounded-full bg-red-500/15 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </template>
            <p class="text-xs text-slate-200" x-text="toast.message"></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function myReportsPage() {
    return {
        loading: true,
        profile: {},
        reports: [],
        activeFilter: 'all',
        editingId: null,
        editForm: { title: '', description: '', category: '' },
        editError: '',
        saving: false,
        showDeleteModal: false,
        deleteTarget: null,
        deleting: false,
        toast: { show: false, message: '', type: 'success' },

        get filteredReports() {
            if (this.activeFilter === 'all') return this.reports;
            return this.reports.filter(r => r.type === this.activeFilter);
        },

        get incidentCount() {
            return this.reports.filter(r => r.type === 'incident').length;
        },

        get stationCount() {
            return this.reports.filter(r => r.type === 'station').length;
        },

        async init() {
            await Promise.all([this.fetchProfile(), this.fetchReports()]);
        },

        async fetchProfile() {
            try {
                const res = await fetch('/api/user/profile', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.profile = data.data || data;
                }
            } catch (e) {
                console.error('Failed to load profile:', e);
            }
        },

        async fetchReports() {
            this.loading = true;
            try {
                const res = await fetch('/api/my-reports', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken() }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.reports = data.data || data;
                }
            } catch (e) {
                console.error('Failed to load reports:', e);
            } finally {
                this.loading = false;
            }
        },

        startEdit(report) {
            this.editingId = report.id;
            this.editError = '';
            this.editForm = {
                title: report.title || report.station_name || '',
                description: report.description || '',
                category: report.category || ''
            };
        },

        cancelEdit() {
            this.editingId = null;
            this.editError = '';
            this.editForm = { title: '', description: '', category: '' };
        },

        async saveEdit(report) {
            this.saving = true;
            this.editError = '';
            try {
                const endpoint = report.type === 'station'
                    ? `/api/stations/${report.id}`
                    : `/api/incidents/${report.id}`;

                let body;
                if (report.type === 'station') {
                    body = { station_name: this.editForm.title, note: this.editForm.description };
                } else {
                    body = { title: this.editForm.title, description: this.editForm.description };
                    if (this.editForm.category) {
                        body.category = this.editForm.category;
                    }
                }

                const res = await fetch(endpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify(body)
                });

                if (res.ok) {
                    const updated = await res.json();
                    const idx = this.reports.findIndex(r => r.id === report.id && r.type === report.type);
                    if (idx !== -1) {
                        this.reports[idx] = { ...this.reports[idx], ...updated.data || updated };
                    }
                    this.cancelEdit();
                    this.showToast('บันทึกสำเร็จ', 'success');
                } else {
                    const err = await res.json().catch(() => null);
                    this.editError = err?.message || 'ไม่สามารถบันทึกได้ กรุณาลองใหม่';
                }
            } catch (e) {
                this.editError = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
            } finally {
                this.saving = false;
            }
        },

        confirmDelete(report) {
            this.deleteTarget = report;
            this.showDeleteModal = true;
        },

        async executeDelete() {
            if (!this.deleteTarget) return;
            this.deleting = true;
            try {
                const endpoint = this.deleteTarget.type === 'station'
                    ? `/api/stations/${this.deleteTarget.id}`
                    : `/api/incidents/${this.deleteTarget.id}`;

                const res = await fetch(endpoint, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    }
                });

                if (res.ok) {
                    this.reports = this.reports.filter(r =>
                        !(r.id === this.deleteTarget.id && r.type === this.deleteTarget.type)
                    );
                    this.showDeleteModal = false;
                    this.deleteTarget = null;
                    this.showToast('ลบรายงานสำเร็จ', 'success');
                } else {
                    this.showDeleteModal = false;
                    this.showToast('ไม่สามารถลบได้ กรุณาลองใหม่', 'error');
                }
            } catch (e) {
                this.showDeleteModal = false;
                this.showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            } finally {
                this.deleting = false;
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        getCategoryEmoji(cat) {
            const emojis = {accident:'🚗',flood:'🌊',roadblock:'🚧',checkpoint:'👮',construction:'🏗️',other:'⚠️'};
            return emojis[cat] || '📌';
        },

        getCategoryLabel(cat) {
            const labels = {accident:'อุบัติเหตุ',flood:'น้ำท่วม',roadblock:'ถนนปิด',checkpoint:'จุดตรวจ',construction:'ก่อสร้าง',other:'อื่นๆ'};
            return labels[cat] || cat;
        },

        getCategoryStyle(cat) {
            const map = {
                flood: 'bg-blue-500/15 text-blue-400 border border-blue-500/20',
                fire: 'bg-red-500/15 text-red-400 border border-red-500/20',
                accident: 'bg-yellow-500/15 text-yellow-400 border border-yellow-500/20',
                crime: 'bg-purple-500/15 text-purple-400 border border-purple-500/20',
                road: 'bg-orange-500/15 text-orange-400 border border-orange-500/20',
                power: 'bg-amber-500/15 text-amber-400 border border-amber-500/20',
                water: 'bg-cyan-500/15 text-cyan-400 border border-cyan-500/20',
                other: 'bg-slate-500/15 text-slate-400 border border-slate-500/20'
            };
            return map[cat] || map.other;
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                return new Date(dateStr).toLocaleDateString('th-TH', {
                    year: 'numeric', month: 'short', day: 'numeric'
                });
            } catch { return dateStr; }
        },

        timeAgo(dateStr) {
            if (!dateStr) return '';
            const now = new Date();
            const date = new Date(dateStr);
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'เมื่อสักครู่';
            if (diff < 3600) return Math.floor(diff / 60) + ' นาทีที่แล้ว';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ชั่วโมงที่แล้ว';
            if (diff < 604800) return Math.floor(diff / 86400) + ' วันที่แล้ว';
            return this.formatDate(dateStr);
        }
    };
}
</script>
@endpush
