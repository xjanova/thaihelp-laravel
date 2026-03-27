@extends('layouts.app')
@section('needs-gmaps', true)

@section('content')
<div class="min-h-screen px-4 py-4 pb-24" x-data="reportPage()">

    {{-- Toast --}}
    <div x-show="toast.show" x-transition
         :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
         class="fixed top-4 left-4 right-4 z-50 rounded-xl px-4 py-3 text-white text-sm shadow-xl flex items-center justify-between">
        <span x-text="toast.message"></span>
        <button @click="toast.show = false" class="ml-2 text-white/70 hover:text-white">&times;</button>
    </div>

    {{-- Success Overlay --}}
    <div x-show="successOverlay" x-transition class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-6">
        <div class="metal-panel rounded-2xl p-6 max-w-sm w-full text-center">
            <div class="text-5xl mb-3">✅</div>
            <h3 class="text-lg font-bold text-white mb-2">รายงานสำเร็จ!</h3>
            <p class="text-sm text-slate-300 mb-1">ขอบคุณที่ช่วยรายงานค่ะ</p>
            <p class="text-orange-400 font-bold mb-4">+5 ⭐</p>
            <div class="flex gap-2">
                <button @click="successOverlay = false" class="flex-1 metal-btn py-2.5 rounded-xl text-sm">รายงานอีก</button>
                <a href="/" class="flex-1 metal-btn-accent py-2.5 rounded-xl text-sm text-center">กลับแผนที่</a>
            </div>
        </div>
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
                <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover" onerror="this.style.display='none'">
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
            <label class="text-xs text-slate-400 mb-2 block">ประเภทเหตุการณ์ <span class="text-red-400">*</span></label>
            <div class="grid grid-cols-3 gap-2">
                @foreach($categories as $cat)
                <button type="button"
                    @click="incident.category = '{{ $cat }}'"
                    :class="incident.category === '{{ $cat }}'
                        ? 'ring-2 ring-orange-500 bg-orange-500/15 scale-[1.05] shadow-[0_0_12px_rgba(249,115,22,0.4)] z-10'
                        : 'metal-panel-hover z-0'"
                    class="metal-panel rounded-xl p-3 text-center transition-all duration-200 relative">
                    <div class="text-2xl mb-1">{{ $categoryEmoji[$cat] ?? '📌' }}</div>
                    <div class="text-[11px]" :class="incident.category === '{{ $cat }}' ? 'text-orange-300 font-semibold' : 'text-slate-300'">{{ $categoryLabels[$cat] ?? $cat }}</div>
                    <div x-show="incident.category === '{{ $cat }}'" x-transition.scale
                         class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center shadow-lg">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Severity --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-2 block">ความรุนแรง</label>
            <div class="flex gap-2">
                <button @click="incident.severity = 'low'" :class="incident.severity === 'low' ? 'ring-2 ring-green-500 bg-green-500/15 shadow-[0_0_10px_rgba(34,197,94,0.3)] scale-[1.03]' : 'metal-panel-hover'" class="metal-panel flex-1 rounded-lg p-2 text-center text-xs transition-all duration-200" :class2="incident.severity === 'low' ? 'text-green-300 font-semibold' : 'text-slate-300'"><span :class="incident.severity === 'low' ? 'text-green-300 font-semibold' : 'text-slate-300'">🟢 เล็กน้อย</span></button>
                <button @click="incident.severity = 'medium'" :class="incident.severity === 'medium' ? 'ring-2 ring-yellow-500 bg-yellow-500/15 shadow-[0_0_10px_rgba(234,179,8,0.3)] scale-[1.03]' : 'metal-panel-hover'" class="metal-panel flex-1 rounded-lg p-2 text-center text-xs transition-all duration-200"><span :class="incident.severity === 'medium' ? 'text-yellow-300 font-semibold' : 'text-slate-300'">🟡 ปานกลาง</span></button>
                <button @click="incident.severity = 'high'" :class="incident.severity === 'high' ? 'ring-2 ring-orange-500 bg-orange-500/15 shadow-[0_0_10px_rgba(249,115,22,0.3)] scale-[1.03]' : 'metal-panel-hover'" class="metal-panel flex-1 rounded-lg p-2 text-center text-xs transition-all duration-200"><span :class="incident.severity === 'high' ? 'text-orange-300 font-semibold' : 'text-slate-300'">🟠 รุนแรง</span></button>
                <button @click="incident.severity = 'critical'" :class="incident.severity === 'critical' ? 'ring-2 ring-red-500 bg-red-500/15 shadow-[0_0_10px_rgba(239,68,68,0.3)] scale-[1.03]' : 'metal-panel-hover'" class="metal-panel flex-1 rounded-lg p-2 text-center text-xs transition-all duration-200"><span :class="incident.severity === 'critical' ? 'text-red-300 font-semibold' : 'text-slate-300'">🔴 วิกฤต</span></button>
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

        {{-- GPS Warning --}}
        <div x-show="!lat || !lng" class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 mb-3 flex items-center gap-2">
            <span class="text-lg">📍</span>
            <div>
                <p class="text-xs text-red-400 font-medium">ต้องเปิด GPS เพื่อรายงาน</p>
                <button @click="refreshLocation()" class="text-[10px] text-orange-400 underline">กดเพื่อขอสิทธิ์ GPS</button>
            </div>
        </div>

        @guest
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 mb-3 text-xs text-blue-300">
            💡 <a href="/login" class="underline text-orange-400">เข้าสู่ระบบ</a> เพื่อรับ ⭐ คะแนนจากการรายงาน
        </div>
        @endguest

        {{-- Submit --}}
        <button @click="submitIncident()" :disabled="!canSubmitIncident() || incidentSubmitting"
                :class="canSubmitIncident() && !incidentSubmitting ? 'metal-btn-accent glow-orange' : 'metal-btn opacity-50'"
                class="w-full py-3 rounded-xl text-sm font-semibold text-white flex items-center justify-center gap-2">
            <template x-if="incidentSubmitting">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            </template>
            <span x-text="incidentSubmitting ? 'กำลังส่ง...' : '📤 ส่งรายงานเหตุการณ์'"></span>
        </button>

        {{-- Nearby Incidents (auto-loaded) --}}
        <div class="mt-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-white">📍 รายงานใกล้เคียง</h2>
                <button @click="loadNearbyIncidents()" class="text-xs text-orange-400 hover:text-orange-300">🔄 รีเฟรช</button>
            </div>
            <div x-show="nearbyLoading" class="text-center py-4">
                <div class="inline-block w-5 h-5 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin"></div>
                <p class="text-xs text-slate-500 mt-2">กำลังโหลดรายงานใกล้เคียง...</p>
            </div>
            <div x-show="!nearbyLoading && nearbyIncidents.length === 0" class="text-center py-4">
                <p class="text-xs text-slate-500">ไม่มีรายงานใกล้เคียงตอนนี้ — ปลอดภัยดีค่ะ ✨</p>
            </div>
            <div class="space-y-2">
                <template x-for="inc in nearbyIncidents" :key="inc.id">
                    <div class="metal-panel rounded-xl p-3">
                        <div class="flex items-start gap-2">
                            <span class="text-lg" x-text="inc.emoji || '📌'"></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-white truncate" x-text="inc.title"></span>
                                    <span class="text-[10px] text-slate-500 ml-2 whitespace-nowrap" x-text="inc.time_ago"></span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5" x-text="inc.category_label"></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full"
                                          :class="inc.severity === 'critical' ? 'bg-red-500/20 text-red-400' : inc.severity === 'high' ? 'bg-orange-500/20 text-orange-400' : inc.severity === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-green-500/20 text-green-400'"
                                          x-text="inc.severity_label"></span>
                                    <span class="text-[10px] text-slate-500" x-text="inc.distance_text"></span>
                                    <span class="text-[10px] text-blue-400" x-text="'👍 ' + (inc.confirmation_count || 0)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ═══ TAB 2: FUEL STATION ═══ --}}
    <div x-show="activeTab === 'fuel'" x-transition>

        {{-- Station Name --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-1 block">ชื่อปั๊ม <span class="text-red-400">*</span></label>
            <input type="text" x-model="fuel.stationName" maxlength="255"
                   class="metal-input w-full px-3 py-2.5 rounded-xl text-sm text-white placeholder-slate-500"
                   placeholder="เช่น PTT สุขุมวิท 39">
            {{-- Quick brand buttons with real logos --}}
            <div class="flex gap-1.5 mt-2 flex-wrap">
                <template x-for="b in brandList" :key="b.id">
                    <button type="button" @click="fuel.stationName = b.name + ' '"
                            :class="fuel.stationName.toLowerCase().startsWith(b.id) ? 'ring-2 ring-orange-500 bg-orange-500/10' : ''"
                            class="metal-btn px-2 py-1.5 rounded-lg flex items-center gap-1.5 hover:bg-white/5">
                        <img :src="'/images/brands/' + b.id + '.webp'" class="w-5 h-5 rounded" :alt="b.name" onerror="this.style.display='none'">
                        <span class="text-[10px] text-slate-300" x-text="b.name"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Reporter Name --}}
        <div class="mb-3">
            <label class="text-xs text-slate-400 mb-1 block">ชื่อผู้รายงาน</label>
            <input type="text" x-model="fuel.reporterName" maxlength="100"
                   class="metal-input w-full px-3 py-2.5 rounded-xl text-sm text-white placeholder-slate-500"
                   placeholder="{{ auth()->user()?->nickname ?? auth()->user()?->name ?? 'ไม่ระบุชื่อ' }}">
        </div>

        {{-- Fuel Types --}}
        <div class="mb-4">
            <label class="text-xs text-slate-400 mb-2 block">ชนิดน้ำมัน <span class="text-red-400">*</span> (เลือกที่มีข้อมูล)</label>
            <div class="grid grid-cols-3 gap-2 mb-3">
                <template x-for="ft in fuelTypes" :key="ft.key">
                    <button type="button" @click="toggleFuel(ft.key)"
                            :class="fuel.selectedFuels[ft.key] ? 'ring-2 ring-orange-500 bg-orange-500/15 shadow-[0_0_10px_rgba(249,115,22,0.3)] scale-[1.03] z-10' : 'metal-panel-hover z-0'"
                            class="metal-panel rounded-lg p-2 text-center transition-all duration-200 relative">
                        <div class="text-xs" :class="fuel.selectedFuels[ft.key] ? 'text-orange-300 font-semibold' : 'text-slate-300'" x-text="ft.label"></div>
                        <div x-show="fuel.selectedFuels[ft.key]" x-transition.scale
                             class="absolute -top-1 -right-1 w-4 h-4 bg-orange-500 rounded-full flex items-center justify-center shadow">
                            <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
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
                           placeholder="ราคา (บาท/ลิตร) — ไม่บังคับ">
                </div>
            </template>
        </div>

        {{-- Facilities --}}
        <div class="mb-4">
            <label class="text-xs text-slate-400 mb-2 block">สิ่งอำนวยความสะดวก</label>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="f in facilityList" :key="f.key">
                    <button type="button" @click="fuel.selectedFacilities[f.key] = !fuel.selectedFacilities[f.key]"
                            :class="fuel.selectedFacilities[f.key] ? 'ring-2 ring-blue-500 bg-blue-500/15 shadow-[0_0_12px_rgba(59,130,246,0.35)] scale-[1.03] z-10' : 'metal-panel-hover z-0'"
                            class="metal-panel rounded-lg p-2.5 text-left flex items-center gap-2 transition-all duration-200 relative">
                        <span class="text-sm" x-text="f.icon"></span>
                        <span class="text-xs" :class="fuel.selectedFacilities[f.key] ? 'text-blue-300 font-semibold' : 'text-slate-300'" x-text="f.label"></span>
                        <div x-show="fuel.selectedFacilities[f.key]" x-transition.scale
                             class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center shadow-lg">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
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
            <p class="text-[10px] text-slate-500 mt-1" x-show="lat">
                <span x-text="lat?.toFixed(6)"></span>, <span x-text="lng?.toFixed(6)"></span>
            </p>
        </div>

        {{-- GPS Warning --}}
        <div x-show="!lat || !lng" class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 mb-3 flex items-center gap-2">
            <span class="text-lg">📍</span>
            <div>
                <p class="text-xs text-red-400 font-medium">ต้องเปิด GPS เพื่อรายงาน</p>
                <button @click="refreshLocation()" class="text-[10px] text-orange-400 underline">กดเพื่อขอสิทธิ์ GPS</button>
            </div>
        </div>

        @guest
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 mb-3 text-xs text-blue-300">
            💡 <a href="/login" class="underline text-orange-400">เข้าสู่ระบบ</a> เพื่อรับ ⭐ คะแนนจากการรายงาน
        </div>
        @endguest

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
        successOverlay: false,
        listening: false,
        voiceTranscript: '',
        voiceProcessing: false,
        voiceReply: '',

        brandList: [
            { id: 'ptt', name: 'PTT' },
            { id: 'shell', name: 'Shell' },
            { id: 'bangchak', name: 'Bangchak' },
            { id: 'esso', name: 'Esso' },
            { id: 'caltex', name: 'Caltex' },
            { id: 'susco', name: 'Susco' },
            { id: 'pt', name: 'PT' },
            { id: 'irpc', name: 'IRPC' },
            { id: 'lpg', name: 'LPG' },
        ],

        incident: { category: '', severity: 'medium', title: '', description: '' },
        incidentSubmitting: false,
        nearbyIncidents: [],
        nearbyLoading: false,

        fuel: {
            stationName: '',
            reporterName: '',
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
            // Auto-load nearby incidents after getting location
            setTimeout(() => this.loadNearbyIncidents(), 2000);
        },

        async loadNearbyIncidents() {
            if (!this.lat || !this.lng) {
                // Retry after location is available
                setTimeout(() => { if (this.lat && this.lng) this.loadNearbyIncidents(); }, 3000);
                return;
            }
            this.nearbyLoading = true;
            try {
                const res = await fetch(`/api/incidents?lat=${this.lat}&lng=${this.lng}&radius=30`);
                const data = await res.json();
                const categoryEmoji = {
                    accident: '🚗', flood: '🌊', roadblock: '🚧', checkpoint: '👮',
                    construction: '🏗️', fuel_shortage: '⛽', fire: '🔥',
                    protest: '✊', crime: '🚨', other: '📌'
                };
                const categoryLabels = {
                    accident: 'อุบัติเหตุ', flood: 'น้ำท่วม', roadblock: 'ถนนปิด',
                    checkpoint: 'ด่านตรวจ', construction: 'ก่อสร้าง', fuel_shortage: 'น้ำมันหมด',
                    fire: 'เพลิงไหม้', protest: 'ชุมนุม', crime: 'อาชญากรรม', other: 'อื่นๆ'
                };
                const severityLabels = { critical: 'วิกฤต', high: 'รุนแรง', medium: 'ปานกลาง', low: 'เล็กน้อย' };

                if (data.success) {
                    this.nearbyIncidents = (data.data || []).slice(0, 10).map(inc => {
                        const diff = Math.floor((Date.now() - new Date(inc.created_at).getTime()) / 1000);
                        let timeAgo = 'เมื่อสักครู่';
                        if (diff >= 86400) timeAgo = Math.floor(diff / 86400) + ' วันที่แล้ว';
                        else if (diff >= 3600) timeAgo = Math.floor(diff / 3600) + ' ชม.ที่แล้ว';
                        else if (diff >= 60) timeAgo = Math.floor(diff / 60) + ' นาทีที่แล้ว';

                        let distText = '';
                        if (inc.distance_km != null) {
                            distText = inc.distance_km >= 1
                                ? inc.distance_km.toFixed(1) + ' กม.'
                                : Math.round(inc.distance_km * 1000) + ' ม.';
                        }

                        return {
                            ...inc,
                            emoji: categoryEmoji[inc.category] || '📌',
                            category_label: categoryLabels[inc.category] || inc.category,
                            severity_label: severityLabels[inc.severity] || inc.severity,
                            time_ago: timeAgo,
                            distance_text: distText ? '📍 ' + distText : '',
                        };
                    });
                }
            } catch (e) {
                console.error('Failed to load nearby incidents:', e);
            }
            this.nearbyLoading = false;
        },

        // ─── Location with reverse geocode ───
        refreshLocation() {
            if (!navigator.geolocation) {
                this.locationName = 'เบราว์เซอร์ไม่รองรับ GPS';
                return;
            }
            this.locationName = 'กำลังหาตำแหน่ง...';
            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.locationName = `${this.lat.toFixed(4)}, ${this.lng.toFixed(4)}`;

                    // Try reverse geocode
                    try {
                        if (window.google?.maps?.Geocoder) {
                            const geocoder = new google.maps.Geocoder();
                            geocoder.geocode({ location: { lat: this.lat, lng: this.lng } }, (results, status) => {
                                if (status === 'OK' && results[0]) {
                                    this.locationName = results[0].formatted_address;
                                }
                            });
                        }
                    } catch (e) { /* keep lat/lng as fallback */ }
                },
                (err) => {
                    this.locationName = 'ไม่สามารถหาตำแหน่งได้ — กรุณาเปิด GPS';
                },
                { enableHighAccuracy: true, timeout: 10000 }
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
                        this.showToast('error', 'ไม่สามารถฟังเสียงได้ กรุณาอนุญาตใช้ไมโครโฟน');
                    }
                });
            } else {
                this.listening = false;
                this.showToast('error', 'เบราว์เซอร์ไม่รองรับการฟังเสียง');
            }
        },

        async processVoiceCommand(transcript) {
            this.voiceProcessing = true;

            try {
                const res = await fetch('/api/voice-command', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
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

                    if (reply && window.sayText) window.sayText(reply);

                    if (action === 'FUEL_REPORT' || action === 'FIND_DIESEL' || action === 'FIND_GASOHOL') {
                        this.activeTab = 'fuel';
                        if (fuelType) {
                            this.fuel.selectedFuels = { ...this.fuel.selectedFuels, [fuelType]: true };
                            this.fuel.fuelData = { ...this.fuel.fuelData, [fuelType]: { status: fuelStatus || 'available', price: '' } };
                        }
                        const stationMatch = transcript.match(/(PTT|Shell|Bangchak|Esso|Caltex|ปตท|เชลล์|บางจาก|เอสโซ่|คาลเท็กซ์|ซัสโก้|Susco)[\w\s]*/i);
                        if (stationMatch) {
                            this.fuel.stationName = stationMatch[0].trim().substring(0, 50);
                        }
                        this.showToast('success', 'น้องหญิงกรอกข้อมูลให้แล้ว ตรวจสอบแล้วกดส่งค่ะ');
                    } else if (action === 'INCIDENT' || action === 'REPORT') {
                        this.activeTab = 'incident';
                        this.incident.title = transcript.substring(0, 100);
                        this.incident.description = transcript;
                        // Auto-detect category
                        const catMap = { 'อุบัติเหตุ': 'accident', 'ชน': 'accident', 'น้ำท่วม': 'flood', 'ถนนปิด': 'roadblock', 'จุดตรวจ': 'checkpoint', 'ก่อสร้าง': 'construction' };
                        for (const [kw, cat] of Object.entries(catMap)) {
                            if (transcript.includes(kw)) { this.incident.category = cat; break; }
                        }
                        this.showToast('success', 'น้องหญิงกรอกให้แล้ว ตรวจสอบแล้วกดส่งค่ะ');
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
            return this.fuel.stationName.trim() && hasSelectedFuel && this.lat && this.lng;
        },

        // ─── Submit Incident ───
        async submitIncident() {
            if (!this.canSubmitIncident()) return;
            this.incidentSubmitting = true;

            try {
                const res = await fetch('/api/incidents', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({
                        category: this.incident.category,
                        title: this.incident.title.trim(),
                        description: this.incident.description.trim() || null,
                        severity: this.incident.severity,
                        latitude: this.lat,
                        longitude: this.lng,
                    }),
                });

                const data = await res.json();
                this.incidentSubmitting = false;

                if (data.success) {
                    if (window.sayText) window.sayText('รายงานเรียบร้อยค่ะ ขอบคุณนะคะ');
                    this.incident = { category: '', severity: 'medium', title: '', description: '' };
                    this.successOverlay = true;
                } else {
                    this.showToast('error', data.message || 'ส่งไม่สำเร็จ กรุณาลองใหม่');
                }
            } catch (e) {
                this.incidentSubmitting = false;
                this.showToast('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
            }
        },

        // ─── Submit Fuel ───
        async submitFuel() {
            if (!this.canSubmitFuel()) return;
            this.fuelSubmitting = true;

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

            const facilities = Object.entries(this.fuel.selectedFacilities)
                .filter(([k, v]) => v)
                .map(([k]) => k);

            try {
                const res = await fetch('/api/stations/report', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({
                        placeId: 'user_report_' + Date.now(),
                        stationName: this.fuel.stationName.trim(),
                        reporterName: this.fuel.reporterName.trim() || null,
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
                    if (window.sayText) window.sayText('บันทึกข้อมูลปั๊มเรียบร้อยค่ะ ขอบคุณนะคะ');
                    this.fuel = { stationName: '', reporterName: '', selectedFuels: {}, fuelData: {}, selectedFacilities: {}, note: '' };
                    this.successOverlay = true;
                } else {
                    this.showToast('error', data.message || 'ส่งไม่สำเร็จ กรุณาลองใหม่');
                }
            } catch (e) {
                this.fuelSubmitting = false;
                this.showToast('error', 'เกิดข้อผิดพลาด กรุณาลองใหม่');
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
