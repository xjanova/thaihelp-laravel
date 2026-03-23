@extends('layouts.app')

@section('content')
@php
    $version = 'dev';
    $versionFile = base_path('version.txt');
    if (file_exists($versionFile)) {
        $version = trim(file_get_contents($versionFile));
    }
@endphp

<div class="min-h-screen flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
        {{-- Logo & App Name --}}
        <div class="text-center mb-8">
            <img src="/images/logo.webp" class="w-20 h-20 rounded-2xl mx-auto mb-4 shadow-xl border-2 border-orange-500/30"
                 alt="ThaiHelp" onerror="this.src='/images/logo.png'">
            <h1 class="text-2xl font-bold text-white">ThaiHelp</h1>
            <p class="text-sm text-slate-400 mt-1">ชุมชนช่วยเหลือนักเดินทางไทย</p>
            <div class="inline-block mt-2 px-3 py-1 rounded-full bg-orange-500/20 border border-orange-500/30">
                <span class="text-xs text-orange-400 font-medium">v{{ $version }}</span>
            </div>
        </div>

        {{-- Team Section --}}
        <div class="metal-panel rounded-2xl p-5 mb-4 border border-slate-700">
            <h2 class="text-sm font-bold text-white mb-4 text-center">ทีมพัฒนา</h2>

            <div class="space-y-4">
                {{-- XMAN Studio --}}
                <div class="flex items-center gap-4 bg-slate-800/50 rounded-xl p-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                        X
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">XMAN Studio</p>
                        <p class="text-[11px] text-slate-400">Design, Architecture, Product Owner</p>
                    </div>
                </div>

                {{-- Claude AI --}}
                <div class="flex items-center gap-4 bg-slate-800/50 rounded-xl p-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                        AI
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Claude AI (Anthropic)</p>
                        <p class="text-[11px] text-slate-400">Co-developer, Code Generation</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Nong Ying --}}
        <div class="metal-panel rounded-2xl p-5 mb-4 border border-slate-700 text-center">
            <img src="/images/ying.webp" class="w-16 h-16 rounded-full mx-auto mb-3 border-2 border-orange-400 shadow-lg"
                 alt="น้องหญิง" onerror="this.style.display='none'">
            <p class="text-sm font-bold text-white">น้องหญิง</p>
            <p class="text-[11px] text-slate-400">AI ผู้ช่วยนักเดินทาง</p>
            <p class="text-[10px] text-slate-500 mt-1">Powered by Groq LLaMA 3.3 70B</p>
        </div>

        {{-- Social Links --}}
        <div class="metal-panel rounded-2xl p-5 mb-4 border border-slate-700">
            <h2 class="text-sm font-bold text-white mb-3 text-center">ติดตามเรา</h2>
            <div class="space-y-2">
                <a href="https://discord.com/channels/1485495002024116294/1485495002699272224" target="_blank"
                   class="flex items-center gap-3 bg-[#5865F2]/20 hover:bg-[#5865F2]/30 rounded-xl px-4 py-3 transition-colors">
                    <span class="text-xl">💬</span>
                    <div>
                        <p class="text-sm font-medium text-white">Discord</p>
                        <p class="text-[10px] text-slate-400">เข้ากลุ่มชุมชน ThaiHelp</p>
                    </div>
                </a>
                <a href="https://www.facebook.com/groups/1196995685631749" target="_blank"
                   class="flex items-center gap-3 bg-[#1877F2]/20 hover:bg-[#1877F2]/30 rounded-xl px-4 py-3 transition-colors">
                    <span class="text-xl">📘</span>
                    <div>
                        <p class="text-sm font-medium text-white">Facebook</p>
                        <p class="text-[10px] text-slate-400">กลุ่ม ThaiHelp</p>
                    </div>
                </a>
                <a href="https://line.me" target="_blank"
                   class="flex items-center gap-3 bg-[#06C755]/20 hover:bg-[#06C755]/30 rounded-xl px-4 py-3 transition-colors">
                    <span class="text-xl">💚</span>
                    <div>
                        <p class="text-sm font-medium text-white">LINE</p>
                        <p class="text-[10px] text-slate-400">LINE Official Account</p>
                    </div>
                </a>
            </div>
        </div>

        {{-- Contact --}}
        <div class="metal-panel rounded-2xl p-5 mb-4 border border-slate-700 text-center">
            <h2 class="text-sm font-bold text-white mb-2">ติดต่อเรา</h2>
            <p class="text-xs text-slate-400">สำหรับข้อเสนอแนะ รายงานปัญหา หรือร่วมพัฒนา</p>
            <p class="text-xs text-orange-400 mt-2">contact@xman4289.com</p>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6 mb-4">
            <p class="text-xs text-slate-500">Built with ❤️ for Thailand</p>
            <p class="text-[10px] text-slate-600 mt-1">© {{ date('Y') }} XMAN Studio. All rights reserved.</p>
        </div>

        {{-- Back Button --}}
        <div class="text-center">
            <a href="/" class="inline-block metal-btn px-6 py-2 rounded-xl text-sm text-slate-300 hover:text-white transition-colors">
                ← กลับหน้าหลัก
            </a>
        </div>
    </div>
</div>
@endsection
