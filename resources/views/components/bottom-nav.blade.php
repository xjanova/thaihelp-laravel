{{-- Fixed Bottom Navigation --}}
<nav class="chrome-bar-bottom fixed bottom-0 left-0 right-0 z-50 h-16 safe-bottom">
    <div class="flex items-center justify-around h-16 px-2">
        {{-- Map Tab --}}
        <a href="/" class="flex flex-col items-center justify-center gap-1 w-16 py-1 {{ Request::is('/') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <span class="text-[10px] font-medium">แผนที่</span>
            @if(Request::is('/'))
            <span class="absolute bottom-1 w-6 h-0.5 rounded-full bg-orange-500"></span>
            @endif
        </a>

        {{-- Report Tab --}}
        <a href="/report" class="flex flex-col items-center justify-center gap-1 w-16 py-1 {{ Request::is('report*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span class="text-[10px] font-medium">แจ้งเหตุ</span>
            @if(Request::is('report*'))
            <span class="absolute bottom-1 w-6 h-0.5 rounded-full bg-orange-500"></span>
            @endif
        </a>

        {{-- Stations Tab --}}
        <a href="/stations" class="flex flex-col items-center justify-center gap-1 w-16 py-1 {{ Request::is('stations*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h3l2-4h8l2 4h3v10a1 1 0 01-1 1h-1l-1 2H6l-1-2H4a1 1 0 01-1-1V7z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span class="text-[10px] font-medium">ปั๊มน้ำมัน</span>
            @if(Request::is('stations*'))
            <span class="absolute bottom-1 w-6 h-0.5 rounded-full bg-orange-500"></span>
            @endif
        </a>

        {{-- My Reports Tab (auth only) --}}
        @auth
        <a href="/my-reports" class="flex flex-col items-center justify-center gap-1 w-16 py-1 {{ Request::is('my-reports*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-[10px] font-medium">ประวัติ</span>
            @if(Request::is('my-reports*'))
            <span class="absolute bottom-1 w-6 h-0.5 rounded-full bg-orange-500"></span>
            @endif
        </a>
        @endauth

        {{-- Chat Tab --}}
        <a href="/chat" class="flex flex-col items-center justify-center gap-1 w-16 py-1 {{ Request::is('chat*') ? 'text-orange-500' : 'text-slate-400 hover:text-slate-300' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
            </svg>
            <span class="text-[10px] font-medium">AI Chat</span>
            @if(Request::is('chat*'))
            <span class="absolute bottom-1 w-6 h-0.5 rounded-full bg-orange-500"></span>
            @endif
        </a>
    </div>
</nav>
