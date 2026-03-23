@extends('layouts.app')

@section('content')
<div class="min-h-screen px-4 py-4" x-data="reportForm()">
    {{-- Title --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-chrome">แจ้งเหตุ</h1>
        <p class="text-sm text-slate-400 mt-1">รายงานเหตุการณ์หรือปัญหาในพื้นที่</p>
    </div>

    {{-- Category Selection --}}
    <div class="mb-5">
        <label class="block text-xs text-slate-400 mb-2">ประเภทเหตุการณ์</label>
        <div class="grid grid-cols-3 gap-2">
            <button @click="category = 'accident'" :class="category === 'accident' ? 'ring-2 ring-orange-500 glow-orange' : ''"
                    class="metal-panel rounded-xl p-3 text-center transition-all">
                <div class="text-2xl mb-1">🚗</div>
                <div class="text-[10px] text-slate-300">อุบัติเหตุ</div>
            </button>
            <button @click="category = 'flood'" :class="category === 'flood' ? 'ring-2 ring-orange-500 glow-orange' : ''"
                    class="metal-panel rounded-xl p-3 text-center transition-all">
                <div class="text-2xl mb-1">🌊</div>
                <div class="text-[10px] text-slate-300">น้ำท่วม</div>
            </button>
            <button @click="category = 'fire'" :class="category === 'fire' ? 'ring-2 ring-orange-500 glow-orange' : ''"
                    class="metal-panel rounded-xl p-3 text-center transition-all">
                <div class="text-2xl mb-1">🔥</div>
                <div class="text-[10px] text-slate-300">ไฟไหม้</div>
            </button>
            <button @click="category = 'road'" :class="category === 'road' ? 'ring-2 ring-orange-500 glow-orange' : ''"
                    class="metal-panel rounded-xl p-3 text-center transition-all">
                <div class="text-2xl mb-1">🚧</div>
                <div class="text-[10px] text-slate-300">ถนนชำรุด</div>
            </button>
            <button @click="category = 'crime'" :class="category === 'crime' ? 'ring-2 ring-orange-500 glow-orange' : ''"
                    class="metal-panel rounded-xl p-3 text-center transition-all">
                <div class="text-2xl mb-1">🚨</div>
                <div class="text-[10px] text-slate-300">อาชญากรรม</div>
            </button>
            <button @click="category = 'other'" :class="category === 'other' ? 'ring-2 ring-orange-500 glow-orange' : ''"
                    class="metal-panel rounded-xl p-3 text-center transition-all">
                <div class="text-2xl mb-1">📢</div>
                <div class="text-[10px] text-slate-300">อื่นๆ</div>
            </button>
        </div>
    </div>

    {{-- Title Input --}}
    <div class="mb-4">
        <label class="block text-xs text-slate-400 mb-1.5">หัวข้อ</label>
        <input type="text" x-model="title"
               class="metal-input w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none"
               placeholder="ระบุหัวข้อเหตุการณ์">
    </div>

    {{-- Description --}}
    <div class="mb-4">
        <label class="block text-xs text-slate-400 mb-1.5">รายละเอียด</label>
        <textarea x-model="description" rows="4"
                  class="metal-input w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none resize-none"
                  placeholder="อธิบายรายละเอียดเหตุการณ์..."></textarea>
    </div>

    {{-- Location --}}
    <div class="mb-5">
        <label class="block text-xs text-slate-400 mb-1.5">ตำแหน่ง</label>
        <div class="metal-panel rounded-xl p-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <template x-if="lat && lng">
                        <div>
                            <p class="text-xs text-slate-300 truncate" x-text="locationName || 'ตำแหน่งปัจจุบัน'"></p>
                            <p class="text-[10px] text-slate-500" x-text="lat.toFixed(6) + ', ' + lng.toFixed(6)"></p>
                        </div>
                    </template>
                    <template x-if="!lat">
                        <p class="text-xs text-slate-500">กำลังหาตำแหน่ง...</p>
                    </template>
                </div>
                <button @click="getLocation()" class="metal-btn px-2 py-1 rounded-lg">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Error Messages --}}
    <template x-if="error">
        <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20">
            <p class="text-sm text-red-400" x-text="error"></p>
        </div>
    </template>

    {{-- Success Message --}}
    <template x-if="success">
        <div class="mb-4 p-3 rounded-lg bg-green-500/10 border border-green-500/20">
            <p class="text-sm text-green-400">แจ้งเหตุสำเร็จ! ขอบคุณที่ช่วยรายงาน</p>
        </div>
    </template>

    {{-- Submit Button --}}
    <button @click="submit()" :disabled="submitting || !category || !title"
            :class="(!category || !title) ? 'opacity-50 cursor-not-allowed' : ''"
            class="metal-btn-accent w-full py-3 rounded-xl text-sm font-semibold text-white">
        <span x-show="!submitting">แจ้งเหตุ</span>
        <span x-show="submitting" class="flex items-center justify-center gap-2">
            <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            กำลังส่ง...
        </span>
    </button>
</div>
@endsection

@push('scripts')
<script>
    function reportForm() {
        return {
            category: '',
            title: '',
            description: '',
            lat: null,
            lng: null,
            locationName: '',
            submitting: false,
            error: '',
            success: false,

            init() {
                this.getLocation();
            },

            getLocation() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.lat = pos.coords.latitude;
                            this.lng = pos.coords.longitude;
                        },
                        (err) => {
                            console.error('Geolocation error:', err);
                            this.error = 'ไม่สามารถหาตำแหน่งได้ กรุณาเปิด GPS';
                        }
                    );
                }
            },

            async submit() {
                if (!this.category || !this.title) {
                    this.error = 'กรุณาเลือกประเภทและระบุหัวข้อ';
                    return;
                }

                this.submitting = true;
                this.error = '';
                this.success = false;

                try {
                    const response = await fetch('/api/incidents', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            category: this.category,
                            title: this.title,
                            description: this.description,
                            latitude: this.lat,
                            longitude: this.lng,
                        }),
                    });

                    if (!response.ok) throw new Error('Failed to submit report');

                    this.success = true;
                    this.category = '';
                    this.title = '';
                    this.description = '';
                } catch (err) {
                    this.error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                } finally {
                    this.submitting = false;
                }
            }
        };
    }
</script>
@endpush
