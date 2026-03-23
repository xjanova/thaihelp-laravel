@extends('layouts.app')

@section('content')
<div class="min-h-screen px-4 py-4" x-data="reportPage()">

    {{-- Toast Notification --}}
    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
         :class="toast.type === 'success' ? 'bg-green-500/90 border-green-400' : 'bg-red-500/90 border-red-400'"
         class="fixed top-16 left-4 right-4 z-50 p-3 rounded-xl border backdrop-blur-sm shadow-lg"
         style="display:none;">
        <div class="flex items-center gap-2">
            <span x-text="toast.type === 'success' ? '✅' : '❌'" class="text-lg"></span>
            <p class="text-sm text-white font-medium flex-1" x-text="toast.message"></p>
            <button @click="toast.show = false" class="text-white/70 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <template x-if="toast.type === 'success'">
            <div class="mt-2 flex gap-2">
                <button @click="window.location.href='/'" class="text-xs bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded-lg transition">🏠 กลับหน้าหลัก</button>
                <button @click="toast.show = false; resetForm()" class="text-xs bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded-lg transition">📝 รายงานเพิ่ม</button>
            </div>
        </template>
    </div>

    {{-- Page Header --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-300">
            📋 รายงานข้อมูล
        </h1>
        <p class="text-sm text-slate-400 mt-1">ช่วยรายงานเหตุการณ์ให้ชุมชนปลอดภัย</p>
    </div>

    {{-- Voice Mode Toggle --}}
    <div class="mb-4">
        <button @click="voiceMode = !voiceMode"
                :class="voiceMode ? 'ring-2 ring-orange-500 glow-orange bg-orange-500/10' : ''"
                class="metal-panel w-full rounded-xl p-3 flex items-center justify-between transition-all duration-300">
            <div class="flex items-center gap-3">
                <span class="text-2xl" :class="voiceMode ? 'animate-pulse' : ''">🎤</span>
                <div>
                    <p class="text-sm font-medium text-white">พูดกับน้องหญิง</p>
                    <p class="text-[10px] text-slate-400">สั่งงานด้วยเสียง</p>
                </div>
            </div>
            <div class="relative w-12 h-6 rounded-full transition-colors duration-300"
                 :class="voiceMode ? 'bg-orange-500' : 'bg-slate-600'">
                <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-300"
                     :class="voiceMode ? 'translate-x-6' : 'translate-x-0.5'"></div>
            </div>
        </button>

        {{-- Voice Panel --}}
        <div x-show="voiceMode" x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="mt-3 metal-panel rounded-xl p-4" style="display:none;">
            <div class="flex flex-col items-center gap-3">
                <button @click="toggleVoice()"
                        :class="listening ? 'bg-red-500 animate-pulse shadow-lg shadow-red-500/30' : 'bg-gradient-to-br from-orange-500 to-amber-500 hover:from-orange-400 hover:to-amber-400'"
                        class="w-20 h-20 rounded-full flex items-center justify-center transition-all duration-300">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"/>
                    </svg>
                </button>
                <p class="text-xs text-slate-400" x-text="listening ? '🔴 กำลังฟัง...' : 'กดเพื่อพูด'"></p>
                <div x-show="voiceTranscript" class="w-full metal-panel rounded-lg p-3 mt-1" style="display:none;">
                    <p class="text-xs text-slate-400 mb-1">คุณพูดว่า:</p>
                    <p class="text-sm text-white" x-text="voiceTranscript"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Switcher --}}
    <div class="flex gap-2 mb-5">
        <button @click="activeTab = 'incident'"
                :class="activeTab === 'incident' ? 'metal-btn-accent glow-orange' : 'metal-btn'"
                class="flex-1 py-2.5 rounded-xl text-sm font-medium transition-all duration-300">
            🚨 เหตุการณ์
        </button>
        <button @click="activeTab = 'fuel'"
                :class="activeTab === 'fuel' ? 'metal-btn-accent glow-orange' : 'metal-btn'"
                class="flex-1 py-2.5 rounded-xl text-sm font-medium transition-all duration-300">
            ⛽ ปั๊มน้ำมัน
        </button>
    </div>

    {{-- ============================================ --}}
    {{-- TAB 1: INCIDENT REPORT --}}
    {{-- ============================================ --}}
    <div x-show="activeTab === 'incident'" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

        {{-- Category Grid --}}
        <div class="mb-5">
            <label class="block text-xs text-slate-400 mb-2 font-medium">ประเภทเหตุการณ์</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach($categories as $cat)
                @php
                    $colorMap = [
                        'accident' => 'border-red-500/50 hover:border-red-400',
                        'flood' => 'border-blue-500/50 hover:border-blue-400',
                        'roadblock' => 'border-orange-500/50 hover:border-orange-400',
                        'checkpoint' => 'border-indigo-500/50 hover:border-indigo-400',
                        'construction' => 'border-yellow-500/50 hover:border-yellow-400',
                        'other' => 'border-gray-500/50 hover:border-gray-400',
                    ];
                    $selectedColorMap = [
                        'accident' => 'ring-red-500 border-red-400 bg-red-500/10',
                        'flood' => 'ring-blue-500 border-blue-400 bg-blue-500/10',
                        'roadblock' => 'ring-orange-500 border-orange-400 bg-orange-500/10',
                        'checkpoint' => 'ring-indigo-500 border-indigo-400 bg-indigo-500/10',
                        'construction' => 'ring-yellow-500 border-yellow-400 bg-yellow-500/10',
                        'other' => 'ring-gray-500 border-gray-400 bg-gray-500/10',
                    ];
                    $borderClass = $colorMap[$cat] ?? 'border-gray-500/50 hover:border-gray-400';
                    $selectedClass = $selectedColorMap[$cat] ?? 'ring-gray-500 border-gray-400 bg-gray-500/10';
                @endphp
                <button @click="incident.category = '{{ $cat }}'"
                        :class="incident.category === '{{ $cat }}' ? 'ring-2 {{ $selectedClass }} scale-105' : ''"
                        class="metal-panel-hover rounded-xl p-3 text-center transition-all duration-200 border {{ $borderClass }}">
                    <div class="text-2xl mb-1">{{ $categoryEmoji[$cat] ?? '⚠️' }}</div>
                    <div class="text-[10px] text-slate-300 font-medium">{{ $categoryLabels[$cat] ?? $cat }}</div>
                </button>
                @endforeach
            </div>
        </div>

        {{-- Title Input --}}
        <div class="mb-4">
            <label class="block text-xs text-slate-400 mb-1.5 font-medium">หัวข้อ <span class="text-red-400">*</span></label>
            <input type="text" x-model="incident.title"
                   class="w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none bg-slate-800/50 border border-slate-700/50 focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/30 transition-all"
                   placeholder="เช่น รถชนบนถนนสุขุมวิท">
        </div>

        {{-- Description --}}
        <div class="mb-4">
            <label class="block text-xs text-slate-400 mb-1.5 font-medium">รายละเอียด <span class="text-slate-600">(ไม่บังคับ)</span></label>
            <textarea x-model="incident.description" rows="3"
                      class="w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none resize-none bg-slate-800/50 border border-slate-700/50 focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/30 transition-all"
                      placeholder="อธิบายสิ่งที่เกิดขึ้น..."></textarea>
        </div>

        {{-- Mini Map --}}
        <div class="mb-4">
            <label class="block text-xs text-slate-400 mb-1.5 font-medium">📍 ตำแหน่งของคุณ</label>
            <div id="report-map" class="w-full h-[200px] rounded-xl overflow-hidden border border-slate-700/50 bg-slate-800/50 flex items-center justify-center">
                <template x-if="!lat">
                    <div class="text-center">
                        <div class="w-8 h-8 border-2 border-orange-500/30 border-t-orange-500 rounded-full animate-spin mx-auto mb-2"></div>
                        <p class="text-xs text-slate-500">กำลังโหลดแผนที่...</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Location Display --}}
        <div class="mb-4">
            <div class="metal-panel rounded-xl p-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <template x-if="lat && lng">
                            <div>
                                <p class="text-xs text-slate-300 truncate" x-text="locationName || 'ตำแหน่งปัจจุบัน'"></p>
                                <p class="text-[10px] text-slate-500" x-text="lat.toFixed(6) + ', ' + lng.toFixed(6)"></p>
                            </div>
                        </template>
                        <template x-if="!lat">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 border-2 border-blue-500/30 border-t-blue-500 rounded-full animate-spin"></div>
                                <p class="text-xs text-slate-500">กำลังหาตำแหน่ง...</p>
                            </div>
                        </template>
                    </div>
                    <button @click="getLocation()" class="metal-btn px-2.5 py-1.5 rounded-lg hover:bg-slate-700/50 transition-colors" title="รีเฟรชตำแหน่ง">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Image URL --}}
        <div class="mb-5">
            <label class="block text-xs text-slate-400 mb-1.5 font-medium">🖼️ ลิงก์รูปภาพ <span class="text-slate-600">(ไม่บังคับ)</span></label>
            <input type="url" x-model="incident.imageUrl"
                   class="w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none bg-slate-800/50 border border-slate-700/50 focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/30 transition-all"
                   placeholder="https://example.com/photo.jpg">
            <template x-if="incident.imageUrl">
                <div class="mt-2 rounded-xl overflow-hidden border border-slate-700/50">
                    <img :src="incident.imageUrl" class="w-full h-32 object-cover" @error="$el.style.display='none'" alt="preview">
                </div>
            </template>
        </div>

        {{-- Submit Button --}}
        <button @click="submitIncident()" :disabled="incidentSubmitting || !incident.category || !incident.title"
                :class="(!incident.category || !incident.title) ? 'opacity-40 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                class="metal-btn-accent w-full py-3.5 rounded-xl text-sm font-bold text-white transition-all duration-200 shadow-lg shadow-orange-500/20">
            <span x-show="!incidentSubmitting" class="flex items-center justify-center gap-2">
                🚨 แจ้งเหตุ
                <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full">+5⭐ คะแนน</span>
            </span>
            <span x-show="incidentSubmitting" class="flex items-center justify-center gap-2" style="display:none;">
                <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                กำลังส่ง...
            </span>
        </button>
    </div>

    {{-- ============================================ --}}
    {{-- TAB 2: FUEL STATION REPORT --}}
    {{-- ============================================ --}}
    <div x-show="activeTab === 'fuel'" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
         style="display:none;">

        {{-- Station Name --}}
        <div class="mb-4">
            <label class="block text-xs text-slate-400 mb-1.5 font-medium">🏪 ชื่อปั๊มน้ำมัน <span class="text-red-400">*</span></label>
            <input type="text" x-model="fuel.stationName"
                   class="w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none bg-slate-800/50 border border-slate-700/50 focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/30 transition-all"
                   placeholder="เช่น ปตท. สาขาลาดพร้าว 71">
        </div>

        {{-- Fuel Types Selection --}}
        <div class="mb-5">
            <label class="block text-xs text-slate-400 mb-2 font-medium">⛽ ชนิดเชื้อเพลิง</label>
            <div class="grid grid-cols-3 gap-2 mb-3">
                <template x-for="ft in fuelTypes" :key="ft.key">
                    <button @click="toggleFuelType(ft.key)"
                            :class="fuel.selectedFuels[ft.key] ? 'ring-2 ring-orange-500 bg-orange-500/10 border-orange-500/50' : 'border-slate-700/50'"
                            class="metal-panel-hover rounded-xl p-2.5 text-center transition-all duration-200 border">
                        <div class="text-lg mb-0.5" x-text="ft.emoji"></div>
                        <div class="text-[9px] text-slate-300 font-medium leading-tight" x-text="ft.label"></div>
                    </button>
                </template>
            </div>

            {{-- Fuel Detail Cards --}}
            <template x-for="ft in fuelTypes" :key="'detail-'+ft.key">
                <div x-show="fuel.selectedFuels[ft.key]"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="metal-panel rounded-xl p-3 mb-2 border border-slate-700/50" style="display:none;">
                    <div class="flex items-center gap-2 mb-2">
                        <span x-text="ft.emoji"></span>
                        <span class="text-xs font-medium text-white" x-text="ft.label"></span>
                    </div>
                    {{-- Status Radio --}}
                    <div class="flex gap-2 mb-2">
                        <button @click="fuel.fuelData[ft.key].status = 'available'"
                                :class="fuel.fuelData[ft.key]?.status === 'available' ? 'bg-green-500/20 border-green-500 text-green-400' : 'border-slate-600 text-slate-400'"
                                class="flex-1 border rounded-lg py-1.5 text-[10px] font-medium transition-all text-center">
                            🟢 มี
                        </button>
                        <button @click="fuel.fuelData[ft.key].status = 'low'"
                                :class="fuel.fuelData[ft.key]?.status === 'low' ? 'bg-yellow-500/20 border-yellow-500 text-yellow-400' : 'border-slate-600 text-slate-400'"
                                class="flex-1 border rounded-lg py-1.5 text-[10px] font-medium transition-all text-center">
                            🟡 ใกล้หมด
                        </button>
                        <button @click="fuel.fuelData[ft.key].status = 'empty'"
                                :class="fuel.fuelData[ft.key]?.status === 'empty' ? 'bg-red-500/20 border-red-500 text-red-400' : 'border-slate-600 text-slate-400'"
                                class="flex-1 border rounded-lg py-1.5 text-[10px] font-medium transition-all text-center">
                            🔴 หมด
                        </button>
                    </div>
                    {{-- Price Input --}}
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-slate-400">ราคา:</span>
                        <input type="number" step="0.01" x-model="fuel.fuelData[ft.key].price"
                               class="flex-1 px-3 py-1.5 rounded-lg text-xs text-white placeholder-slate-500 outline-none bg-slate-800/50 border border-slate-700/50 focus:border-orange-500/50 transition-all"
                               placeholder="0.00">
                        <span class="text-[10px] text-slate-400">บาท/ลิตร</span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Facilities --}}
        <div class="mb-5">
            <label class="block text-xs text-slate-400 mb-2 font-medium">🏗️ สิ่งอำนวยความสะดวก</label>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="fac in facilities" :key="fac.key">
                    <button @click="fuel.selectedFacilities[fac.key] = !fuel.selectedFacilities[fac.key]"
                            :class="fuel.selectedFacilities[fac.key] ? 'ring-1 ring-green-500/50 bg-green-500/10 border-green-500/30' : 'border-slate-700/50'"
                            class="metal-panel-hover rounded-xl p-2.5 flex items-center gap-2 transition-all duration-200 border">
                        <span class="text-lg" x-text="fac.emoji"></span>
                        <span class="text-[11px] text-slate-300" x-text="fac.label"></span>
                        <div class="ml-auto">
                            <div :class="fuel.selectedFacilities[fac.key] ? 'bg-green-500' : 'bg-slate-600'"
                                 class="w-4 h-4 rounded-full flex items-center justify-center transition-colors">
                                <svg x-show="fuel.selectedFacilities[fac.key]" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Location Display for Fuel --}}
        <div class="mb-4">
            <div class="metal-panel rounded-xl p-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <template x-if="lat && lng">
                            <div>
                                <p class="text-xs text-slate-300 truncate" x-text="locationName || 'ตำแหน่งปัจจุบัน'"></p>
                                <p class="text-[10px] text-slate-500" x-text="lat.toFixed(6) + ', ' + lng.toFixed(6)"></p>
                            </div>
                        </template>
                        <template x-if="!lat">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 border-2 border-blue-500/30 border-t-blue-500 rounded-full animate-spin"></div>
                                <p class="text-xs text-slate-500">กำลังหาตำแหน่ง...</p>
                            </div>
                        </template>
                    </div>
                    <button @click="getLocation()" class="metal-btn px-2.5 py-1.5 rounded-lg hover:bg-slate-700/50 transition-colors">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Note --}}
        <div class="mb-5">
            <label class="block text-xs text-slate-400 mb-1.5 font-medium">📝 หมายเหตุ <span class="text-slate-600">(ไม่บังคับ)</span></label>
            <textarea x-model="fuel.note" rows="2"
                      class="w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none resize-none bg-slate-800/50 border border-slate-700/50 focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/30 transition-all"
                      placeholder="เช่น คิวยาวมาก, ปั๊มปิดซ่อมบำรุง..."></textarea>
        </div>

        {{-- Submit Button --}}
        <button @click="submitFuel()" :disabled="fuelSubmitting || !fuel.stationName"
                :class="(!fuel.stationName) ? 'opacity-40 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                class="metal-btn-accent w-full py-3.5 rounded-xl text-sm font-bold text-white transition-all duration-200 shadow-lg shadow-orange-500/20">
            <span x-show="!fuelSubmitting" class="flex items-center justify-center gap-2">
                ⛽ รายงานปั๊มน้ำมัน
                <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full">+5⭐ คะแนน</span>
            </span>
            <span x-show="fuelSubmitting" class="flex items-center justify-center gap-2" style="display:none;">
                <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                กำลังส่ง...
            </span>
        </button>
    </div>

    {{-- Bottom spacer --}}
    <div class="h-8"></div>
</div>
@endsection

@push('scripts')
<script>
function reportPage() {
    return {
        activeTab: 'incident',

        // Shared state
        lat: null,
        lng: null,
        locationName: '',
        toast: { show: false, type: 'success', message: '' },

        // Voice
        voiceMode: false,
        listening: false,
        voiceTranscript: '',

        // Incident form
        incident: {
            category: '',
            title: '',
            description: '',
            imageUrl: '',
        },
        incidentSubmitting: false,

        // Fuel form
        fuel: {
            stationName: '',
            selectedFuels: {},
            fuelData: {},
            selectedFacilities: {},
            note: '',
        },
        fuelSubmitting: false,

        // Fuel type definitions
        fuelTypes: [
            { key: 'gasohol95', label: 'แก๊สโซฮอล์ 95', emoji: '🟢' },
            { key: 'gasohol91', label: 'แก๊สโซฮอล์ 91', emoji: '🟡' },
            { key: 'e20', label: 'E20', emoji: '🌿' },
            { key: 'e85', label: 'E85', emoji: '🌱' },
            { key: 'diesel', label: 'ดีเซล', emoji: '🔵' },
            { key: 'diesel_b7', label: 'ดีเซล B7', emoji: '💧' },
            { key: 'premium_diesel', label: 'ดีเซลพรีเมียม', emoji: '💎' },
            { key: 'ngv', label: 'NGV', emoji: '🔷' },
            { key: 'lpg', label: 'LPG', emoji: '🟠' },
        ],

        // Facilities definitions
        facilities: [
            { key: 'air_pump', label: 'ที่เติมลม', emoji: '🌀' },
            { key: 'restroom', label: 'ห้องน้ำ', emoji: '🚻' },
            { key: 'convenience', label: 'ร้านสะดวกซื้อ', emoji: '🏪' },
            { key: 'car_wash', label: 'ล้างรถ', emoji: '🚿' },
            { key: 'coffee', label: 'ร้านกาแฟ', emoji: '☕' },
            { key: 'wifi', label: 'WiFi ฟรี', emoji: '📶' },
        ],

        map: null,
        mapMarker: null,

        init() {
            this.getLocation();
            // Initialize fuel data structure
            this.fuelTypes.forEach(ft => {
                this.fuel.fuelData[ft.key] = { status: 'available', price: '' };
            });
            this.facilities.forEach(fac => {
                this.fuel.selectedFacilities[fac.key] = false;
            });
        },

        getLocation() {
            if (!navigator.geolocation) {
                this.showToast('error', 'เบราว์เซอร์ไม่รองรับ GPS');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.reverseGeocode();
                    this.initMap();
                },
                (err) => {
                    console.error('Geolocation error:', err);
                    this.showToast('error', 'ไม่สามารถหาตำแหน่งได้ กรุณาเปิด GPS');
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        reverseGeocode() {
            if (!this.lat || !this.lng) return;
            if (typeof google !== 'undefined' && google.maps) {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat: this.lat, lng: this.lng } }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        this.locationName = results[0].formatted_address;
                    }
                });
            }
        },

        initMap() {
            if (!this.lat || !this.lng) return;
            const mapEl = document.getElementById('report-map');
            if (!mapEl) return;
            if (typeof google === 'undefined' || !google.maps) {
                mapEl.innerHTML = '<div class="flex items-center justify-center h-full"><p class="text-xs text-slate-500">📍 ' + this.lat.toFixed(4) + ', ' + this.lng.toFixed(4) + '</p></div>';
                return;
            }
            const pos = { lat: this.lat, lng: this.lng };
            this.map = new google.maps.Map(mapEl, {
                center: pos,
                zoom: 15,
                disableDefaultUI: true,
                zoomControl: true,
                styles: [
                    { elementType: 'geometry', stylers: [{ color: '#1a1a2e' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#1a1a2e' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#8a8a9a' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2a2a3e' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1a2b' }] },
                ],
            });
            this.mapMarker = new google.maps.Marker({
                position: pos,
                map: this.map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: '#f97316',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                },
            });
        },

        toggleFuelType(key) {
            this.fuel.selectedFuels[key] = !this.fuel.selectedFuels[key];
            if (this.fuel.selectedFuels[key] && !this.fuel.fuelData[key]) {
                this.fuel.fuelData[key] = { status: 'available', price: '' };
            }
        },

        toggleVoice() {
            if (this.listening) {
                this.listening = false;
                if (window.stopListening) window.stopListening();
                return;
            }
            this.listening = true;
            this.voiceTranscript = '';
            if (window.startListening) {
                window.startListening((transcript) => {
                    this.voiceTranscript = transcript;
                    this.listening = false;
                    this.sendVoiceCommand(transcript);
                });
            } else {
                // Fallback: Web Speech API
                try {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    const recognition = new SpeechRecognition();
                    recognition.lang = 'th-TH';
                    recognition.interimResults = false;
                    recognition.onresult = (e) => {
                        const transcript = e.results[0][0].transcript;
                        this.voiceTranscript = transcript;
                        this.listening = false;
                        this.sendVoiceCommand(transcript);
                    };
                    recognition.onerror = () => {
                        this.listening = false;
                        this.showToast('error', 'ไม่สามารถรับเสียงได้');
                    };
                    recognition.onend = () => { this.listening = false; };
                    recognition.start();
                } catch (e) {
                    this.listening = false;
                    this.showToast('error', 'เบราว์เซอร์ไม่รองรับการรับเสียง');
                }
            }
        },

        async sendVoiceCommand(transcript) {
            try {
                const res = await fetch('/api/voice-command', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ text: transcript, context: 'report' }),
                });
                const data = await res.json();
                if (data.reply) {
                    this.showToast('success', data.reply);
                }
            } catch (e) {
                console.error('Voice command error:', e);
            }
        },

        async submitIncident() {
            if (!this.incident.category || !this.incident.title) {
                this.showToast('error', 'กรุณาเลือกประเภทและระบุหัวข้อ');
                return;
            }
            this.incidentSubmitting = true;
            try {
                const res = await fetch('/api/incidents', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        category: this.incident.category,
                        title: this.incident.title,
                        description: this.incident.description,
                        latitude: this.lat,
                        longitude: this.lng,
                        image_url: this.incident.imageUrl || null,
                    }),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => null);
                    throw new Error(err?.message || 'เกิดข้อผิดพลาด');
                }
                this.showToast('success', '🎉 แจ้งเหตุสำเร็จ! ขอบคุณที่ช่วยรายงาน (+5 คะแนน)');
                this.incident = { category: '', title: '', description: '', imageUrl: '' };
            } catch (e) {
                this.showToast('error', e.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
            } finally {
                this.incidentSubmitting = false;
            }
        },

        async submitFuel() {
            if (!this.fuel.stationName) {
                this.showToast('error', 'กรุณาระบุชื่อปั๊มน้ำมัน');
                return;
            }
            this.fuelSubmitting = true;

            // Build fuel data for selected fuels only
            const fuelReports = [];
            Object.keys(this.fuel.selectedFuels).forEach(key => {
                if (this.fuel.selectedFuels[key] && this.fuel.fuelData[key]) {
                    fuelReports.push({
                        fuel_type: key,
                        status: this.fuel.fuelData[key].status || 'available',
                        price: parseFloat(this.fuel.fuelData[key].price) || null,
                    });
                }
            });

            // Build facilities array
            const facilitiesList = [];
            Object.keys(this.fuel.selectedFacilities).forEach(key => {
                if (this.fuel.selectedFacilities[key]) facilitiesList.push(key);
            });

            try {
                const res = await fetch('/api/stations/report', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        placeId: 'user_report_' + Date.now(),
                        stationName: this.fuel.stationName,
                        reporterName: this.fuel.reporterName || null,
                        fuelReports: fuelReports,
                        facilities: facilitiesList,
                        note: this.fuel.note || null,
                        latitude: this.lat,
                        longitude: this.lng,
                    }),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => null);
                    throw new Error(err?.message || 'เกิดข้อผิดพลาด');
                }
                this.showToast('success', '⛽ รายงานปั๊มน้ำมันสำเร็จ! ขอบคุณค่ะ (+5 คะแนน)');
                this.fuel.stationName = '';
                this.fuel.selectedFuels = {};
                this.fuel.note = '';
                this.fuelTypes.forEach(ft => {
                    this.fuel.fuelData[ft.key] = { status: 'available', price: '' };
                });
                this.facilities.forEach(fac => {
                    this.fuel.selectedFacilities[fac.key] = false;
                });
            } catch (e) {
                this.showToast('error', e.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
            } finally {
                this.fuelSubmitting = false;
            }
        },

        resetForm() {
            if (this.activeTab === 'incident') {
                this.incident = { category: '', title: '', description: '', imageUrl: '' };
            } else {
                this.fuel.stationName = '';
                this.fuel.selectedFuels = {};
                this.fuel.note = '';
            }
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            if (type === 'error') {
                setTimeout(() => { this.toast.show = false; }, 5000);
            } else {
                setTimeout(() => { this.toast.show = false; }, 8000);
            }
        },
    };
}
</script>
@endpush
