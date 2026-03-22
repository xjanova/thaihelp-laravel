{{-- Fixed Top Header --}}
<header class="chrome-bar fixed top-0 left-0 right-0 z-50 h-14 safe-top">
    <div class="flex items-center justify-between h-14 px-4">
        {{-- Left: Logo + Brand --}}
        <a href="/" class="flex items-center gap-2">
            <img src="/images/logo.png" alt="ThaiHelp" class="w-8 h-8 rounded-lg" onerror="this.style.display='none'">
            <span class="text-lg font-bold">
                <span class="text-blue-500">Thai</span><span class="text-orange-500">Help</span>
            </span>
        </a>

        {{-- Right: Auth --}}
        <div class="flex items-center gap-3">
            @auth
                <span class="text-sm text-slate-400">{{ auth()->user()->nickname ?? auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="metal-btn px-3 py-1.5 rounded-lg text-xs text-slate-300 hover:text-white">
                        ออก
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="metal-btn-accent px-4 py-1.5 rounded-lg text-sm font-medium text-white">
                    เข้าใช้งาน
                </a>
            @endauth
        </div>
    </div>
</header>
