{{-- Fixed Bottom Navigation - 5 tabs with น้องหญิง center --}}
<nav class="fixed bottom-0 left-0 right-0 z-50 safe-bottom">
    <div class="chrome-bar-bottom h-16 relative">
        <div class="flex items-end justify-around h-16 px-1">

            {{-- 1. แผนที่ --}}
            <a href="/" class="relative flex flex-col items-center justify-center gap-0.5 w-14 pb-1 pt-2 {{ Request::is('/') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                <span class="text-[10px] font-medium leading-tight">แผนที่</span>
                @if(Request::is('/'))
                <span class="absolute bottom-0 w-6 h-0.5 rounded-full bg-orange-500"></span>
                @endif
            </a>

            {{-- 2. แจ้งเหตุ --}}
            <a href="/report" class="relative flex flex-col items-center justify-center gap-0.5 w-14 pb-1 pt-2 {{ Request::is('report*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span class="text-[10px] font-medium leading-tight">แจ้งเหตุ</span>
                @if(Request::is('report*'))
                <span class="absolute bottom-0 w-6 h-0.5 rounded-full bg-orange-500"></span>
                @endif
            </a>

            {{-- 3. น้องหญิง (CENTER - avatar with speech bubble) --}}
            <div class="relative flex flex-col items-center -mt-5 w-16">
                {{-- Speech bubble (greeting) --}}
                <div id="ying-nav-bubble" class="absolute -top-12 left-1/2 -translate-x-1/2 w-44 bg-white text-gray-800 text-[11px] rounded-xl px-3 py-1.5 shadow-lg opacity-0 transition-opacity duration-500 z-50" style="pointer-events:none;">
                    <span id="ying-nav-bubble-text">สวัสดีค่ะ! ถามหญิงได้นะ</span>
                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45 w-2.5 h-2.5 bg-white"></div>
                </div>

                {{-- Sound toggle --}}
                <button id="ying-sound-toggle" onclick="event.preventDefault(); event.stopPropagation(); toggleYingSound();"
                        class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-slate-800 border border-slate-600 flex items-center justify-center text-[8px] z-50 shadow-md hover:bg-slate-700 transition-colors"
                        title="เปิด/ปิดเสียงน้องหญิง">
                    <span id="ying-sound-icon">🔇</span>
                </button>

                {{-- Avatar link --}}
                <a href="/chat" class="relative block">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-full {{ Request::is('chat*') ? 'bg-orange-500/30 animate-pulse' : 'bg-slate-600/20' }}" style="margin: -3px;"></div>
                        <div class="relative w-12 h-12 rounded-full border-2 {{ Request::is('chat*') ? 'border-orange-500 shadow-lg shadow-orange-500/30' : 'border-slate-600' }} overflow-hidden bg-gradient-to-br from-slate-700 to-slate-800">
                            <img src="/images/ying.png"
                                 alt="น้องหญิง"
                                 class="w-full h-full object-cover"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full items-center justify-center text-xl" style="display:none;">👧</div>
                        </div>
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-slate-900"></span>
                    </div>
                </a>
                <span class="text-[10px] font-medium leading-tight mt-0.5 {{ Request::is('chat*') ? 'text-orange-500' : 'text-slate-400' }}">น้องหญิง</span>
            </div>

            {{-- 4. ปั๊มน้ำมัน --}}
            <a href="/stations" class="relative flex flex-col items-center justify-center gap-0.5 w-14 pb-1 pt-2 {{ Request::is('stations*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <span class="text-[10px] font-medium leading-tight">ปั๊ม</span>
                @if(Request::is('stations*'))
                <span class="absolute bottom-0 w-6 h-0.5 rounded-full bg-orange-500"></span>
                @endif
            </a>

            {{-- 5. อื่นๆ (เมนูรวม) --}}
            <div class="relative flex flex-col items-center justify-center gap-0.5 w-14 pb-1 pt-2" x-data="{ open: false }">
                <button @click="open = !open" class="{{ Request::is('stats*') || Request::is('trip*') || Request::is('hospitals*') || Request::is('fuel-prices*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <span class="text-[10px] font-medium leading-tight block">อื่นๆ</span>
                </button>

                {{-- Flyout Menu --}}
                <div x-show="open" x-transition @click.outside="open = false"
                     class="absolute bottom-14 right-0 w-52 metal-panel rounded-2xl p-2 shadow-2xl border border-slate-700/50 space-y-0.5">

                    <a href="/stats" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-700/50 transition-colors {{ Request::is('stats*') ? 'bg-orange-500/10 text-orange-400' : 'text-slate-300' }}">
                        <span class="text-base">📊</span>
                        <span class="text-xs font-medium">สถิติชุมชน</span>
                    </a>
                    <a href="/trip" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-700/50 transition-colors {{ Request::is('trip*') ? 'bg-orange-500/10 text-orange-400' : 'text-slate-300' }}">
                        <span class="text-base">🗺️</span>
                        <span class="text-xs font-medium">วางแผนเดินทาง</span>
                    </a>
                    <a href="/hospitals" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-700/50 transition-colors {{ Request::is('hospitals*') ? 'bg-orange-500/10 text-orange-400' : 'text-slate-300' }}">
                        <span class="text-base">🏥</span>
                        <span class="text-xs font-medium">สถานพยาบาล</span>
                    </a>
                    <a href="/fuel-prices" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-700/50 transition-colors {{ Request::is('fuel-prices*') ? 'bg-orange-500/10 text-orange-400' : 'text-slate-300' }}">
                        <span class="text-base">⛽</span>
                        <span class="text-xs font-medium">ราคาน้ำมัน</span>
                    </a>

                    <hr class="border-slate-700/50 my-1">

                    {{-- Share --}}
                    <button onclick="shareNative()" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-700/50 transition-colors text-slate-300">
                        <span class="text-base">📤</span>
                        <span class="text-xs font-medium">แชร์ ThaiHelp</span>
                    </button>

                    <div class="flex justify-center gap-2 px-3 py-1">
                        <button onclick="shareToLine()" class="w-8 h-8 rounded-full bg-[#06C755]/20 hover:bg-[#06C755]/30 flex items-center justify-center text-xs transition-colors" title="LINE">💚</button>
                        <button onclick="shareToFacebook()" class="w-8 h-8 rounded-full bg-[#1877F2]/20 hover:bg-[#1877F2]/30 flex items-center justify-center text-xs transition-colors" title="Facebook">💙</button>
                        <button onclick="shareToTwitter()" class="w-8 h-8 rounded-full bg-slate-600/20 hover:bg-slate-600/30 flex items-center justify-center text-xs transition-colors" title="X">🐦</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>
