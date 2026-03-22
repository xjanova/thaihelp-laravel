<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>เข้าใช้งาน - ThaiHelp</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <meta name="theme-color" content="#f97316">

    @vite(['resources/css/app.css'])
</head>
<body class="antialiased font-thai min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, #0a0e17 0%, #111827 50%, #0a0e17 100%);">

    <div class="w-full max-w-sm">
        {{-- Logo + Title --}}
        <div class="text-center mb-6">
            <div class="flex items-center justify-center gap-3 mb-3">
                <img src="/images/logo.png" alt="ThaiHelp" class="w-14 h-14 rounded-2xl" onerror="this.style.display='none'">
                <div>
                    <h1 class="text-3xl font-bold">
                        <span class="text-blue-500">Thai</span><span class="text-orange-500">Help</span>
                    </h1>
                    <p class="text-xs text-slate-500">ช่วยเหลือคนไทย</p>
                </div>
            </div>
        </div>

        {{-- Feature Highlights --}}
        <div class="flex gap-2 mb-6 px-2">
            <div class="metal-panel rounded-xl p-3 flex-1 text-center">
                <div class="text-xl mb-1">⛽</div>
                <div class="text-[10px] text-slate-400">ปั๊มน้ำมัน</div>
            </div>
            <div class="metal-panel rounded-xl p-3 flex-1 text-center">
                <div class="text-xl mb-1">🚨</div>
                <div class="text-[10px] text-slate-400">แจ้งเหตุ</div>
            </div>
            <div class="metal-panel rounded-xl p-3 flex-1 text-center">
                <div class="text-xl mb-1">🤖</div>
                <div class="text-[10px] text-slate-400">AI Chat</div>
            </div>
        </div>

        {{-- Login Form --}}
        <div class="metal-panel rounded-2xl p-6">
            <h2 class="text-lg font-semibold text-chrome text-center mb-5">เข้าใช้งาน</h2>

            @if($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20">
                @foreach($errors->all() as $error)
                <p class="text-sm text-red-400">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Nickname --}}
                <div class="mb-4">
                    <label class="block text-xs text-slate-400 mb-1.5">ชื่อเล่น</label>
                    <input type="text" name="nickname" value="{{ old('nickname') }}" required
                           class="metal-input w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none"
                           placeholder="ใส่ชื่อเล่นของคุณ">
                </div>

                {{-- Email (optional) --}}
                <div class="mb-5">
                    <label class="block text-xs text-slate-400 mb-1.5">
                        อีเมล <span class="text-slate-600">(ไม่บังคับ)</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="metal-input w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none"
                           placeholder="example@email.com">
                </div>

                {{-- Submit --}}
                <button type="submit" class="metal-btn-accent w-full py-3 rounded-xl text-sm font-semibold text-white">
                    เข้าใช้งาน
                </button>
            </form>

            {{-- Divider --}}
            <div class="flex items-center gap-3 my-5">
                <div class="metal-divider flex-1"></div>
                <span class="text-xs text-slate-500">หรือเข้าสู่ระบบด้วย</span>
                <div class="metal-divider flex-1"></div>
            </div>

            {{-- Social Login Buttons --}}
            <div class="flex flex-col gap-3">
                {{-- Google --}}
                <a href="{{ url('/auth/google') }}" class="flex items-center justify-center gap-3 w-full py-2.5 rounded-xl text-sm font-medium bg-white text-gray-800 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </a>

                {{-- LINE --}}
                <a href="{{ url('/auth/line') }}" class="flex items-center justify-center gap-3 w-full py-2.5 rounded-xl text-sm font-medium text-white hover:opacity-90 transition-opacity" style="background-color: #06C755;">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="white">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                    LINE
                </a>
            </div>
        </div>

        {{-- Bottom Text --}}
        <p class="text-center text-xs text-slate-500 mt-4">
            ไม่ต้องสมัครสมาชิก ใส่ชื่อเล่นเพื่อเข้าใช้งานได้เลย
        </p>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
