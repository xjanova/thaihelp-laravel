<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ThaiHelp - Setup</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased font-thai min-h-screen flex items-center justify-center p-4"
      style="background: linear-gradient(145deg, #060a12, #0a1020, #0c1428);">

<div id="setup-app" class="w-full max-w-lg" x-data="setupWizard()" x-cloak>

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-cyan-500 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-orange-500/20">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">ThaiHelp Setup</h1>
        <p class="text-sm text-slate-400 mt-1">ตั้งค่าระบบครั้งแรก</p>
    </div>

    {{-- Progress --}}
    <div class="flex items-center justify-center gap-2 mb-8">
        <template x-for="(s, i) in ['db', 'config', 'done']" :key="s">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                     :class="step === s ? 'bg-cyan-500 text-white scale-110' : (steps.indexOf(step) > i ? 'bg-emerald-500 text-white' : 'bg-slate-800 text-slate-500')">
                    <span x-text="steps.indexOf(step) > i ? '✓' : (i + 1)"></span>
                </div>
                <div x-show="i < 2" class="w-8 h-0.5" :class="steps.indexOf(step) > i ? 'bg-emerald-500' : 'bg-slate-800'"></div>
            </div>
        </template>
    </div>

    {{-- Error --}}
    <div x-show="error" x-transition class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
        <p class="text-sm text-red-400" x-text="error"></p>
    </div>

    {{-- Step 1: Database --}}
    <div x-show="step === 'db'" x-transition class="metal-panel rounded-2xl p-6 space-y-4">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
            </svg>
            <h2 class="text-lg font-bold text-white">ฐานข้อมูล</h2>
        </div>

        <template x-if="!dbReady">
            <div class="space-y-3">
                <p class="text-sm text-yellow-400">ยังไม่ได้สร้างตารางฐานข้อมูล</p>
                <button @click="runMigrations()" :disabled="loading"
                        class="w-full metal-btn-accent text-white font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="loading ? 'กำลังสร้างตาราง...' : 'สร้างฐานข้อมูล'"></span>
                </button>
            </div>
        </template>

        <template x-if="dbReady">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                    <span class="text-sm text-emerald-400">ฐานข้อมูลพร้อมใช้งาน</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="t in tables" :key="t">
                        <span class="text-[10px] bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/20" x-text="t"></span>
                    </template>
                </div>
                <button @click="step = 'config'"
                        class="w-full metal-btn-blue text-white font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2">
                    ถัดไป
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Step 2: Configuration --}}
    <div x-show="step === 'config'" x-transition class="metal-panel rounded-2xl p-6 space-y-5">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            <h2 class="text-lg font-bold text-white">ตั้งค่าเว็บไซต์</h2>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-xs font-medium text-slate-400 mb-1.5 block">ชื่อเว็บไซต์</label>
                <input type="text" x-model="config.site_name" placeholder="ThaiHelp"
                       class="w-full metal-input rounded-lg px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-cyan-500/30">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-400 mb-1.5 block">คำอธิบาย</label>
                <input type="text" x-model="config.site_description" placeholder="ชุมชนช่วยเหลือนักเดินทาง"
                       class="w-full metal-input rounded-lg px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-cyan-500/30">
            </div>

            <div class="metal-divider my-4"></div>
            <p class="text-xs font-medium text-orange-400">บัญชีแอดมิน</p>

            <div>
                <label class="text-xs font-medium text-slate-400 mb-1.5 block">ชื่อแอดมิน</label>
                <input type="text" x-model="config.admin_name" placeholder="Admin"
                       class="w-full metal-input rounded-lg px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-cyan-500/30">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-400 mb-1.5 block">อีเมลแอดมิน</label>
                <input type="email" x-model="config.admin_email" placeholder="admin@thaihelp.com"
                       class="w-full metal-input rounded-lg px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-cyan-500/30">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-400 mb-1.5 block">รหัสผ่านแอดมิน</label>
                <input type="password" x-model="config.admin_password" placeholder="อย่างน้อย 6 ตัวอักษร"
                       class="w-full metal-input rounded-lg px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-cyan-500/30">
            </div>

            <div class="metal-divider my-4"></div>
            <p class="text-xs font-medium text-cyan-400">ตำแหน่งแผนที่เริ่มต้น</p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-slate-400 mb-1.5 block">Latitude</label>
                    <input type="text" x-model="config.default_map_lat" placeholder="13.7563"
                           class="w-full metal-input rounded-lg px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-cyan-500/30">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-400 mb-1.5 block">Longitude</label>
                    <input type="text" x-model="config.default_map_lng" placeholder="100.5018"
                           class="w-full metal-input rounded-lg px-3 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-cyan-500/30">
                </div>
            </div>
        </div>

        <button @click="saveConfig()" :disabled="loading || !config.admin_name || !config.admin_email || !config.admin_password"
                class="w-full bg-gradient-to-r from-orange-500 to-cyan-500 hover:from-orange-600 hover:to-cyan-600 text-white font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2 transition-all disabled:opacity-50">
            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span x-text="loading ? 'กำลังบันทึก...' : 'เสร็จสิ้นการตั้งค่า'"></span>
        </button>
    </div>

    {{-- Step 3: Done --}}
    <div x-show="step === 'done'" x-transition class="metal-panel rounded-2xl p-6 text-center space-y-4">
        <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-white">ตั้งค่าเสร็จสมบูรณ์!</h2>
        <p class="text-sm text-slate-400">ระบบ ThaiHelp พร้อมใช้งานแล้ว</p>

        <div class="bg-slate-800/30 rounded-xl p-4 text-left space-y-2">
            <p class="text-xs text-slate-500 font-medium">สิ่งที่ควรทำต่อ:</p>
            <ul class="text-xs text-slate-400 space-y-1.5">
                <li class="flex items-center gap-2"><span class="text-orange-400">1.</span> ตั้งค่า Google Maps API Key ใน .env</li>
                <li class="flex items-center gap-2"><span class="text-orange-400">2.</span> ตั้งค่า Google/LINE OAuth ใน .env</li>
                <li class="flex items-center gap-2"><span class="text-orange-400">3.</span> ตั้งค่า Groq API Key สำหรับน้องหญิง AI</li>
                <li class="flex items-center gap-2"><span class="text-orange-400">4.</span> เข้า Admin Panel ที่ /admin</li>
            </ul>
        </div>

        <div class="flex gap-3">
            <a href="/" class="flex-1 metal-btn-accent text-white font-bold py-3 rounded-xl text-sm text-center">
                เปิดเว็บไซต์
            </a>
            <a href="/admin" class="flex-1 metal-btn-blue text-white font-bold py-3 rounded-xl text-sm text-center">
                Admin Panel
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function setupWizard() {
    return {
        step: '{{ $dbReady ? "config" : "db" }}',
        steps: ['db', 'config', 'done'],
        loading: false,
        error: '',
        dbReady: {{ $dbReady ? 'true' : 'false' }},
        tables: @json($tables),
        config: {
            site_name: 'ThaiHelp',
            site_description: 'ชุมชนช่วยเหลือนักเดินทาง',
            admin_name: '',
            admin_email: '',
            admin_password: '',
            default_map_lat: '13.7563',
            default_map_lng: '100.5018',
        },

        async runMigrations() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/setup/migrate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.dbReady = true;
                    this.tables = data.tables || [];
                    this.step = 'config';
                } else {
                    this.error = data.error || 'สร้างตารางไม่สำเร็จ';
                }
            } catch (e) {
                this.error = 'เกิดข้อผิดพลาด: ' + e.message;
            }
            this.loading = false;
        },

        async saveConfig() {
            this.loading = true;
            this.error = '';
            try {
                const res = await fetch('/setup/configure', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.config),
                });
                const data = await res.json();
                if (data.success) {
                    this.step = 'done';
                } else {
                    this.error = data.error || 'บันทึกไม่สำเร็จ';
                }
            } catch (e) {
                this.error = 'เกิดข้อผิดพลาด: ' + e.message;
            }
            this.loading = false;
        },
    };
}
</script>
</body>
</html>
