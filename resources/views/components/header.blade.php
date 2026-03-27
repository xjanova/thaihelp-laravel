{{-- Fixed Top Header --}}
<header class="chrome-bar fixed top-0 left-0 right-0 z-50 h-14 safe-top">
    <div class="flex items-center justify-between h-14 px-4">
        {{-- Left: Logo + Brand --}}
        <a href="/" class="flex items-center gap-2">
            <img src="/images/logo.webp" alt="ThaiHelp" class="w-8 h-8 rounded-lg" onerror="this.style.display='none'">
            <span class="text-lg font-bold">
                <span class="text-blue-500">Thai</span><span class="text-orange-500">Help</span>
            </span>
        </a>

        {{-- Right: Donate + Social + Auth --}}
        <div class="flex items-center gap-2">
            {{-- ☕ Donate Button - eye-catching --}}
            <a href="https://xman4289.com/apps/aipray/donate" target="_blank" rel="noopener"
               class="relative flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-bold text-amber-900 transition-all hover:scale-105"
               style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%); box-shadow: 0 0 12px rgba(251,191,36,0.4);"
               title="☕ บริจาคค่ากาแฟนักพัฒนา">
                <span class="text-sm">☕</span>
                <span class="hidden sm:inline">บริจาค</span>
                <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
                <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full"></span>
            </a>

            {{-- LINE Add Friend --}}
            <a href="https://line.me/R/ti/p/@217vdyok" target="_blank" rel="noopener"
               class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-medium bg-[#06C755] text-white hover:bg-[#05b04c] transition-colors"
               title="เพิ่มเพื่อน LINE">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
                LINE
            </a>

            {{-- Facebook Group --}}
            <a href="https://www.facebook.com/groups/1196995685631749" target="_blank" rel="noopener"
               class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-medium bg-[#1877F2] text-white hover:bg-[#166ad5] transition-colors"
               title="Facebook Group">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                FB
            </a>

            {{-- Discord --}}
            <a href="https://discord.com/channels/1485495002024116294/1485495002699272224" target="_blank" rel="noopener"
               class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-medium bg-[#5865F2] text-white hover:bg-[#4752c4] transition-colors"
               title="Discord">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg>
                DC
            </a>

            @auth
                <span class="text-xs text-slate-400 hidden sm:inline">{{ auth()->user()->nickname ?? auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="metal-btn px-2.5 py-1 rounded-lg text-[11px] text-slate-300 hover:text-white">
                        ออก
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="metal-btn-accent px-3 py-1.5 rounded-lg text-xs font-medium text-white">
                    เข้าใช้
                </a>
            @endauth
        </div>
    </div>
</header>
