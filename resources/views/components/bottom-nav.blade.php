{{-- Fixed Bottom Navigation - 5 tabs with น้องหญิง center --}}
<nav class="fixed bottom-0 left-0 right-0 z-50 safe-bottom">
    {{-- Background bar --}}
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

            {{-- 3. น้องหญิง (CENTER - larger avatar) --}}
            <a href="/chat" class="relative flex flex-col items-center -mt-6 w-16">
                {{-- Glow ring --}}
                <div class="relative">
                    <div class="absolute inset-0 rounded-full {{ Request::is('chat*') ? 'bg-orange-500/30 animate-pulse' : 'bg-slate-600/20' }}" style="margin: -3px;"></div>
                    {{-- Avatar container --}}
                    <div class="relative w-12 h-12 rounded-full border-2 {{ Request::is('chat*') ? 'border-orange-500 shadow-lg shadow-orange-500/30' : 'border-slate-600' }} overflow-hidden bg-gradient-to-br from-slate-700 to-slate-800">
                        <img src="/images/ying-avatar.png"
                             alt="น้องหญิง"
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        {{-- Fallback emoji avatar --}}
                        <div class="w-full h-full items-center justify-center text-xl" style="display:none;">👧</div>
                    </div>
                    {{-- Online indicator --}}
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-slate-900"></span>
                </div>
                <span class="text-[10px] font-medium leading-tight mt-0.5 {{ Request::is('chat*') ? 'text-orange-500' : 'text-slate-400' }}">น้องหญิง</span>
            </a>

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

            {{-- 5. สถิติ --}}
            <a href="/stats" class="relative flex flex-col items-center justify-center gap-0.5 w-14 pb-1 pt-2 {{ Request::is('stats*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-[10px] font-medium leading-tight">สถิติ</span>
                @if(Request::is('stats*'))
                <span class="absolute bottom-0 w-6 h-0.5 rounded-full bg-orange-500"></span>
                @endif
            </a>

        </div>
    </div>
</nav>
