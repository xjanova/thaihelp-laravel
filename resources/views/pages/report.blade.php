@extends('layouts.app')

@section('content')
<div class="min-h-screen px-4 py-4" x-data="reportPage()">

    {{-- Toast --}}
    <div x-show="toast.show" x-transition
         :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
         class="fixed top-4 left-4 right-4 z-50 rounded-xl px-4 py-3 text-white text-sm shadow-xl flex items-center justify-between">
        <span x-text="toast.message"></span>
        <button @click="toast.show = false" class="ml-2 text-white/70 hover:text-white">&times;</button>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-lg font-bold text-white">📝 รายงาน</h1>
        <div class="flex items-center gap-2">
            <span class="text-xs text-orange-400">+5⭐ ต่อรายงาน</span>
        </div>
    </div>

    {{-- Voice Mode Banner --}}
    <div class="metal-panel rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-orange-500/50">
                <img src="/images/ying.png" alt="น้องหญิง" class="w-full h-full object-cover">
            </div>
            <div>
                <p class="text-sm font-medium text-white">พูดกับน้องหญิง</p>
                <p class="text-[10px] text-slate-400">กดไมค์แล้วบอกเลยค่ะ เช่น "ปั๊ม PTT น้ำมันหมด"</p>
            </div>
        </div>
        <button @click="toggleVoice()"
                :class="listening ? 'metal-btn-accent glow-orange animate-pulse' : 'metal-btn'"
                class="w-12 h-12 rounded-full flex items-center justify-center">
            <span class="text-xl" x-text="listening ? '🔴' : '🎤'"></span>
        </button>
    </div>

    {{-- Voice Transcript --}}
    <div x-show="voiceTranscript" x-transition class="metal-panel rounded-xl p-3 mb-4">
        <p class="text-xs text-slate-400 mb-1">คุณพูดว่า:</p>
        <p class="text-sm text-white" x-text="voiceTranscript"></p>
        <div x-show="voiceProcessing" class="mt-2 flex items-center gap-2">
            <div class="w-4 h-4 border-2 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
            <span class="text-xs text-orange-400">น้องหญิงกำลังประมวลผล...</span>
        </div>
        <div x-show="voiceReply" class="mt-2 p-2 bg-orange-500/10 rounded-lg">
            <p class="text-xs text-orange-300" x-text="voiceReply"></p>
        </div>
    </div>

    {{-- Tab Switcher --}}
    <div class="flex gap-2 mb-4">
        <button @click="activeTab = 'incident'"
                :class="activeTab === 'incident' ? 'metal-btn-accent' : 'metal-btn'"
                class="flex-1 py-2.5 rounded-xl text-sm font-medium">
            🚨 เหตุการณ์
        </button>
        <button @click="activeTab = 'fuel'"
                :class="activeTab === 'fuel' ? 'metal-btn-accent' : 'metal-btn'"
                class="flex-1 py-2.5 rounded-xl text-sm font-medium">
            ⛽ ปั๊มน้ำมัน
        </button>
    </div>

    {{-- ═══ TAB 1: INCIDENT ═══ --}}
    <div x-show="activeTab === 'incident'" x-transition>

        {{-- Category Grid --}}
        <div class="mb-4">
            <label class="text-xs text-slate-400 mb-2 block">ประเภทเหตุการณ์</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach($categories as $cat)
                <button type="button"
                    @click="incident.category = '{{ $cat }}'"
                    :class="incident.category === '{{ $cat }}' ? 'ring-2 ring-orange-500 bg-orange-500/10' : 'metal-panel-hover'"
                    class="metal-panel rounded-xl p-3 text-center transition-all">
                    <div class="text-2xl mb-1">{{ $categoryEmoji[$cat] ?? '📌' }}</div>
                    <div class="text-[11px] text-slate-300">{{ $categoryLabels[$cat] ?? $cat }}</div>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Title --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-1 block">หัวข้อ <span class="text-red-400">*</span></label>
            <input type="text" x-model="incident.title" maxlength="200"
                   class="metal-input w-full px-3 py-2.5 rounded-xl text-sm text-white placeholder-slate-500"
                   placeholder="เช่น น้ำท่วมถนนสุขุมวิท">
        </div>

        {{-- Description --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-1 block">รายละเอียด</label>
            <textarea x-model="incident.description" rows="3" maxlength="2000"
                      class="metal-input w-full px-3 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 resize-none"
                      placeholder="อธิบายเพิ่มเติม..."></textarea>
        </div>

        {{-- Image URL --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-1 block">รูปภาพ (URL)</label>
            <input type="url" x-model="incident.imageUrl"
                   class="metal-input w-full px-3 py-2.5 rounded-xl text-sm text-white placeholder-slate-500"
                   placeholder="https://...">
        </div>

        {{-- Location --}}
        <div class="metal-panel rounded-xl p-3 mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-slate-400">📍 ตำแหน่ง</span>
                <button @click="refreshLocation()" class="text-xs text-orange-400 hover:text-orange-300">🔄 รีเฟรช</button>
            </div>
            <p class="text-sm text-slate-300" x-text="locationName || 'กำลังหาตำแหน่ง...'"></p>
            <p class="text-[10px] text-slate-500 mt-1" x-show="lat">
                <span x-text="lat?.toFixed(6)"></span>, <span x-text="lng?.toFixed(6)"></span>
            </p>
        </div>

        {{-- Submit --}}
        <button @click="submitIncident()" :disabled="!canSubmitIncident() || incidentSubmitting"
                :class="canSubmitIncident() && !incidentSubmitting ? 'metal-btn-accent glow-orange' : 'metal-btn opacity-50'"
                class="w-full py-3 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2">
            <template x-if="incidentSubmitting">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            </template>
            <span x-text="incidentSubmitting ? 'กำลังส่ง...' : '📤 ส่งรายงานเหตุการณ์'"></span>
        </button>
    </div>

    {{-- ═══ TAB 2: FUEL STATION ═══ --}}
    <div x-show="activeTab === 'fuel'" x-transition>

        {{-- Station Name --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-1 block">ชื่อปั๊ม <span class="text-red-400">*</span></label>
            <input type="text" x-model="fuel.stationName" maxlength="255"
                   class="metal-input w-full px-3 py-2.5 rounded-xl text-sm text-white placeholder-slate-500"
                   placeholder="เช่น PTT สุขุมวิท 39">
        </div>

        {{-- Fuel Types --}}
        <div class="mb-4">
            <label class="text-xs text-slate-400 mb-2 block">ชนิดน้ำมัน (เลือกที่มีข้อมูล)</label>
            <div class="grid grid-cols-3 gap-2 mb-3">
                <template x-for="ft in fuelTypes" :key="ft.key">
                    <button type="button" @click="toggleFuel(ft.key)"
                            :class="fuel.selectedFuels[ft.key] ? 'ring-2 ring-orange-500 bg-orange-500/10' : 'metal-panel-hover'"
                            class="metal-panel rounded-lg p-2 text-center transition-all">
                        <div class="text-xs text-slate-300" x-text="ft.label"></div>
                    </button>
                </template>
            </div>

            {{-- Fuel Details per selected --}}
            <template x-for="ft in fuelTypes" :key="'detail-'+ft.key">
                <div x-show="fuel.selectedFuels[ft.key]" x-transition class="metal-panel rounded-xl p-3 mb-2">
                    <p class="text-xs font-medium text-white mb-2" x-text="ft.label"></p>
                    <div class="flex gap-2 mb-2">
                        <button @click="setFuelStatus(ft.key, 'available')"
                                :class="fuel.fuelData[ft.key]?.status === 'available' ? 'bg-green-600 text-white' : 'metal-btn'"
                                class="flex-1 py-1.5 rounded-lg text-xs text-center">🟢 มี</button>
                        <button @click="setFuelStatus(ft.key, 'low')"
                                :class="fuel.fuelData[ft.key]?.status === 'low' ? 'bg-yellow-600 text-white' : 'metal-btn'"
                                class="flex-1 py-1.5 rounded-lg text-xs text-center">🟡 น้อย</button>
                        <button @click="setFuelStatus(ft.key, 'empty')"
                                :class="fuel.fuelData[ft.key]?.status === 'empty' ? 'bg-red-600 text-white' : 'metal-btn'"
                                class="flex-1 py-1.5 rounded-lg text-xs text-center">🔴 หมด</button>
                    </div>
                    <input type="number" step="0.01" min="0" max="99"
                           :value="fuel.fuelData[ft.key]?.price || ''"
                           @input="setFuelPrice(ft.key, $event.target.value)"
                           class="metal-input w-full px-3 py-2 rounded-lg text-xs text-white placeholder-slate-500"
                           placeholder="ราคา (บาท/ลิตร)">
                </div>
            </template>
        </div>

        {{-- Facilities --}}
        <div class="mb-4">
            <label class="text-xs text-slate-400 mb-2 block">สิ่งอำนวยความสะดวก</label>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="f in facilityList" :key="f.key">
                    <button type="button" @click="fuel.selectedFacilities[f.key] = !fuel.selectedFacilities[f.key]"
                            :class="fuel.selectedFacilities[f.key] ? 'ring-2 ring-blue-500 bg-blue-500/10' : 'metal-panel-hover'"
                            class="metal-panel rounded-lg p-2 text-left flex items-center gap-2 transition-all">
                        <span class="text-sm" x-text="f.icon"></span>
                        <span class="text-xs text-slate-300" x-text="f.label"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Note --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-1 block">หมายเหตุ</label>
            <textarea x-model="fuel.note" rows="2" maxlength="1000"
                      class="metal-input w-full px-3 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 resize-none"
                      placeholder="เช่น คิวยาว 10 คัน, ที่เติมลมเสีย"></textarea>
        </div>

        {{-- Location --}}
        <div class="metal-panel rounded-xl p-3 mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-slate-400">📍 ตำแหน่ง</span>
                <button @click="refreshLocation()" class="text-xs text-orange-400 hover:text-orange-300">🔄 รีเฟรช</button>
            </div>
            <p class="text-sm text-slate-300" x-text="locationName || 'กำลังหาตำแหน่ง...'"></p>
        </div>

        {{-- Submit --}}
        <button @click="submitFuel()" :disabled="!canSubmitFuel() || fuelSubmitting"
                :class="canSubmitFuel() && !fuelSubmitting ? 'metal-btn-accent glow-orange' : 'metal-btn opacity-50'"
                class="w-full py-3 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2">
            <template x-if="fuelSubmitting">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            </template>
            <span x-text="fuelSubmitting ? 'กำลังส่ง...' : '📤 ส่งรายงานปั๊ม'"></span>
        </button>
    </div>

    <div class="h-8"></div>
</div>
@endsection

@push('scripts')
<script>
function reportPage() {
    return {
        activeTab: 'incident',
        lat: null,
        lng: null,
        locationName: '',
        toast: { show: false, type: 'success', message: '' },
        listening: false,
        voiceTranscript: '',
        voiceProcessing: false,
        voiceReply: '',

        incident: { category: '', title: '', description: '', imageUrl: '' },
        incidentSubmitting: false,

        fuel: {
            stationName: '',
            selectedFuels: {},
            fuelData: {},
            selectedFacilities: {},
            note: '',
        },
        fuelSubmitting: false,

        fuelTypes: [
            { key: 'gasohol95', label: 'แก๊สโซฮอล์ 95' },
            { key: 'gasohol91', label: 'แก๊สโซฮอล์ 91' },
            { key: 'e20', label: 'E20' },
            { key: 'e85', label: 'E85' },
            { key: 'diesel', label: 'ดีเซล' },
            { key: 'diesel_b7', label: 'ดีเซล B7' },
            { key: 'premium_diesel', label: 'ดีเซลพรีเมียม' },
            { key: 'ngv', label: 'NGV' },
            { key: 'lpg', label: 'LPG' },
        ],

        facilityList: [
            { key: 'air_pump', label: 'ที่เติมลม', icon: '🌀' },
            { key: 'restroom', label: 'ห้องน้ำ', icon: '🚻' },
            { key: 'convenience', label: 'ร้านสะดวกซื้อ', icon: '🏪' },
            { key: 'car_wash', label: 'ล้างรถ', icon: '🚿' },
            { key: 'coffee', label: 'ร้านกาแฟ', icon: '☕' },
            { key: 'wifi', label: 'WiFi ฟรี', icon: '📶' },
        ],

        init() {
            this.refreshLocation();
        },

        // ─── Location ───
        refreshLocation() {
            if (!navigator.geolocation) {
                this.locationName = 'เบราว์เซอร์ไม่รองรับ GPS';
                return;
            }
            this.locationName = 'กำลังหาตำแหน่ง...';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.locationName = `${this.lat.toFixed(4)}, ${this.lng.toFixed(4)}`;
                },
                () => { this.locationName = 'ไม่สามารถหาตำแหน่งได้'; }
            );
        },

        // ─── Voice ───
        toggleVoice() {
            if (this.listening) {
                this.listening = false;
                if (window.stopListening) window.stopListening();
                return;
            }

            this.listening = true;
            this.voiceTranscript = '';
            this.voiceReply = '';

            if (window.startListening) {
                window.startListening({
                    onResult: (transcript) => {
                        this.listening = false;
                        this.voiceTranscript = transcript;
                        this.processVoiceCommand(transcript);
                    },
                    onInterim: (text) => {
                        this.voiceTranscript = text + '...';
                    },
                    onError: () => {
                        this.listening = false;
                        this.showToast('error', 'ไม่สามารถฟังเสียงได้');
                    }
                });
            } else {
                this.listening = false;
                this.showToast('error', 'เบราว์เซอร์ไม่รองรับการฟังเสียง');
            }
        },

        async processVoiceCommand(transcript) {
            this.voiceProcessing = true;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const res = await fetch('/api/voice-command', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        transcript: transcript,
                        latitude: this.lat,
                        longitude: this.lng,
                    }),
                });

                const data = await res.json();
                this.voiceProcessing = false;

                if (data.success && data.data) {
                    const { reply, action, fuelType, fuelStatus } = data.data;
                    this.voiceReply = reply || '';

                    // TTS: น้องหญิงพูดตอบ
                    if (reply && window.sayText) {
                        window.sayText(reply);
                    }

                    // Auto-populate form based on action
                    if (action === 'FUEL_REPORT' || action === 'FIND_DIESEL' || action === 'FIND_GASOHOL') {
                        this.activeTab = 'fuel';
                        if (fuelType) {
                            this.fuel.selectedFuels = { ...this.fuel.selectedFuels, [fuelType]: true };
                            this.fuel.fuelData = { ...this.fuel.fuelData, [fuelType]: { status: fuelStatus || 'available', price: '' } };
                        }
                        // Try to extract station name from transcript
                        const stationMatch = transcript.match(/(PTT|Shell|Bangchak|Esso|Caltex|ปตท|เชลล์|บางจาก|เอสโซ่|คาลเท็กซ์|ซัสโก้|Susco)[\s\S]*/i);
                        if (stationMatch) {
                            this.fuel.stationName = stationMatch[0].trim().substring(0, 50);
                        }
                        this.showToast('success', 'น้องหญิงกรอกข้อมูลให้แล้ว ตรวจสอบแล้วกดส่งค่ะ');
                    } else if (action === 'INCIDENT' || action === 'REPORT') {
                        this.activeTab = 'incident';
                        this.incident.title = transcript.substring(0, 100);
                        this.incident.description = transcript;
                        this.showToast('success', 'น้องหญิงกรอกให้แล้ว เลือกประเภทแล้วกดส่งค่ะ');
                    }
                } else {
                    this.voiceReply = data.message || 'ไม่เข้าใจค่ะ ลองพูดใหม่นะคะ';
                }
            } catch (e) {
                this.voiceProcessing = false;
                this.voiceReply = 'เกิดข้อผิดพลาด ลองใหม่นะคะ';
            }
        },

        // ─── Fuel helpers ───
        toggleFuel(key) {
            const cur = this.fuel.selectedFuels[key] || false;
            this.fuel.selectedFuels = { ...this.fuel.selectedFuels, [key]: !cur };
            if (!cur && !this.fuel.fuelData[key]) {
                this.fuel.fuelData = { ...this.fuel.fuelData, [key]: { status: 'available', price: '' } };
            }
        },

        setFuelStatus(key, status) {
            const existing = this.fuel.fuelData[key] || {};
            this.fuel.fuelData = { ...this.fuel.fuelData, [key]: { ...existing, status } };
        },

        setFuelPrice(key, price) {
            const existing = this.fuel.fuelData[key] || {};
            this.fuel.fuelData = { ...this.fuel.fuelData, [key]: { ...existing, price: price ? parseFloat(price) : null } };
        },

        // ─── Validation ───
        canSubmitIncident() {
            return this.incident.category && this.incident.title.trim() && this.lat && this.lng;
        },

        canSubmitFuel() {
            const hasSelectedFuel = Object.values(this.fuel.selectedFuels).some(v => v);
            return this.fuel.stationName.trim() && hasSelectedFuel;
        },

        // ─── Submit Incident ───
        async submitIncident() {
            if (!this.canSubmitIncident()) return;
            this.incidentSubmitting = true;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const res = await fetch('/api/incidents', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        category: this.incident.category,
                        title: this.incident.title.trim(),
                        description: this.incident.description.trim() || null,
                        latitude: this.lat,
                        longitude: this.lng,
                        image_url: this.incident.imageUrl || null,
                    }),
                });

                const data = await res.json();
                this.incidentSubmitting = false;

                if (data.success) {
                    this.showToast('success', 'รายงานสำเร็จ! +5⭐');
                    if (window.sayText) window.sayText('รายงานเรียบร้อยค่ะ ขอบคุณนะคะ');
                    this.incident = { category: '', title: '', description: '', imageUrl: '' };
                } else {
                    this.showToast('error', data.message || 'ส่งไม่สำเร็จ');
                }
            } catch (e) {
                this.incidentSubmitting = false;
                this.showToast('error', 'เกิดข้อผิดพลาด ลองใหม่');
            }
        },

        // ─── Submit Fuel ───
        async submitFuel() {
            if (!this.canSubmitFuel()) return;
            this.fuelSubmitting = true;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            // Build fuelReports array
            const fuelReports = [];
            for (const [key, selected] of Object.entries(this.fuel.selectedFuels)) {
                if (!selected) continue;
                const data = this.fuel.fuelData[key] || {};
                fuelReports.push({
                    fuel_type: key,
                    status: data.status || 'available',
                    price: data.price ? parseFloat(data.price) : null,
                });
            }

            // Build facilities array
            const facilities = Object.entries(this.fuel.selectedFacilities)
                .filter(([k, v]) => v)
                .map(([k]) => k);

            try {
                const res = await fetch('/api/stations/report', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        placeId: 'user_report_' + Date.now(),
                        stationName: this.fuel.stationName.trim(),
                        fuelReports: fuelReports,
                        facilities: facilities.length > 0 ? facilities : null,
                        note: this.fuel.note.trim() || null,
                        latitude: this.lat,
                        longitude: this.lng,
                    }),
                });

                const data = await res.json();
                this.fuelSubmitting = false;

                if (data.success) {
                    this.showToast('success', 'รายงานปั๊มสำเร็จ! +5⭐');
                    if (window.sayText) window.sayText('บันทึกข้อมูลปั๊มเรียบร้อยค่ะ ขอบคุณนะคะ');
                    this.fuel = { stationName: '', selectedFuels: {}, fuelData: {}, selectedFacilities: {}, note: '' };
                } else {
                    this.showToast('error', data.message || 'ส่งไม่สำเร็จ');
                }
            } catch (e) {
                this.fuelSubmitting = false;
                this.showToast('error', 'เกิดข้อผิดพลาด ลองใหม่');
            }
        },

        // ─── Toast ───
        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 5000);
        },
    };
}
</script>
@endpush
