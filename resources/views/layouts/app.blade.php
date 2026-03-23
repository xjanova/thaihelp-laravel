<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ThaiHelp - ช่วยเหลือคนไทย' }}</title>

    {{-- Google Fonts: Noto Sans Thai --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/logo.png">
    <meta name="theme-color" content="#f97316">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    {{-- Vite CSS --}}
    @vite(['resources/css/app.css'])

    {{-- Google Maps JS API --}}
    @php $gmapsKey = \App\Services\ApiKeyPool::getKey('google_maps') ?: \App\Models\SiteSetting::get('google_maps_api_key') ?: config('services.google_maps.api_key', ''); @endphp
    @if($gmapsKey)
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $gmapsKey }}&libraries=places&language=th" defer></script>
    @endif

    @stack('styles')
</head>
<body class="antialiased font-thai">
    {{-- ═══ Intro Video (first visit only) ═══ --}}
    <div id="intro-overlay" style="display:none;"
         class="fixed inset-0 z-[9999] bg-black flex flex-col items-center justify-center cursor-pointer">
        {{-- App frame: logo + border --}}
        <div class="absolute top-0 left-0 right-0 z-10 flex items-center justify-center gap-2 py-3 bg-gradient-to-b from-black/80 to-transparent">
            <img src="/images/logo.png" class="w-7 h-7 rounded-lg" alt="ThaiHelp" onerror="this.style.display='none'">
            <span class="text-white font-bold text-sm tracking-wide">ThaiHelp</span>
            <span class="text-orange-400 text-[10px] ml-1 border border-orange-400/50 rounded px-1.5 py-0.5">WELCOME</span>
        </div>
        <div class="absolute inset-0 border-2 border-orange-500/30 rounded-lg pointer-events-none z-10"></div>

        {{-- Tap to start prompt (shown first) --}}
        <div id="intro-tap" class="text-center animate-pulse">
            <img src="/images/ying.png" class="w-20 h-20 rounded-full border-2 border-orange-400 mx-auto mb-4 shadow-xl" alt="น้องหญิง" onerror="this.style.display='none'">
            <p class="text-white text-lg font-medium">สวัสดีค่ะ! ยินดีต้อนรับสู่ ThaiHelp</p>
            <p class="text-orange-400 text-sm mt-2">แตะเพื่อเริ่มต้น ▶</p>
        </div>

        {{-- Video (hidden until tap) --}}
        <video id="intro-video" class="max-h-screen max-w-screen object-contain opacity-0 absolute inset-0 w-full h-full"
               style="transition: opacity 1s;"
               playsinline preload="auto"
               src="/media/open1.mp4">
        </video>

        <button id="intro-skip" onclick="closeIntro()" style="display:none;"
                class="absolute bottom-8 right-6 text-white/40 hover:text-white text-xs px-4 py-2 rounded-full border border-white/20 hover:border-white/50 transition-all z-10">
            ข้ามไป →
        </button>
    </div>
    <script>
        (function() {
            const INTRO_KEY = 'thaihelp_intro_seen';
            const INTRO_VIDEO_COUNT_KEY = 'ying_video_play_count';
            const MIN_VOLUME = 0.6;

            if (localStorage.getItem(INTRO_KEY)) return;

            const overlay = document.getElementById('intro-overlay');
            const video = document.getElementById('intro-video');
            const tapPrompt = document.getElementById('intro-tap');
            const skipBtn = document.getElementById('intro-skip');

            overlay.style.display = 'flex';

            // User taps → start video WITH sound
            overlay.addEventListener('click', function startVideo(e) {
                if (e.target === skipBtn || skipBtn.contains(e.target)) return;

                overlay.removeEventListener('click', startVideo);
                tapPrompt.style.display = 'none';

                // Unmute + force volume ≥ 60%
                video.muted = false;
                video.volume = Math.max(MIN_VOLUME, video.volume);
                video.style.opacity = '1';
                video.play().catch(() => {
                    // Fallback: play muted if browser still blocks
                    video.muted = true;
                    video.play().catch(() => {});
                });

                // Show skip after 3s
                setTimeout(() => { skipBtn.style.display = 'block'; }, 3000);
            }, { once: false });

            // Auto close when video ends
            video.addEventListener('ended', () => {
                setTimeout(closeIntro, 800);
            });

            window.closeIntro = function() {
                localStorage.setItem(INTRO_KEY, Date.now());
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.8s';
                setTimeout(() => { overlay.remove(); }, 900);
            };

            // Replay for chat (with sound)
            window.replayIntroVideo = function() {
                const count = parseInt(localStorage.getItem(INTRO_VIDEO_COUNT_KEY) || '0');
                if (count >= 3) return false;
                localStorage.setItem(INTRO_VIDEO_COUNT_KEY, count + 1);

                const div = document.createElement('div');
                div.className = 'fixed inset-0 z-[9999] bg-black/95 flex items-center justify-center';
                div.style.animation = 'fadeIn 0.5s ease-out';
                const vid = document.createElement('video');
                vid.className = 'max-h-screen max-w-screen object-contain';
                vid.setAttribute('playsinline', '');
                vid.src = '/media/open1.mp4';
                vid.volume = Math.max(MIN_VOLUME, 0.6);
                vid.muted = false;

                const closeBtn = document.createElement('button');
                closeBtn.className = 'absolute top-4 right-4 text-white/50 hover:text-white text-xl z-10';
                closeBtn.innerHTML = '&times;';
                closeBtn.onclick = () => { div.style.opacity='0'; div.style.transition='opacity 0.8s'; setTimeout(()=>div.remove(),900); };

                vid.onended = closeBtn.onclick;
                div.appendChild(vid);
                div.appendChild(closeBtn);
                document.body.appendChild(div);
                vid.play().catch(() => { vid.muted = true; vid.play().catch(()=>{}); });
                return true;
            };

            window.getVideoPlayCount = function() {
                return parseInt(localStorage.getItem(INTRO_VIDEO_COUNT_KEY) || '0');
            };
        })();
    </script>

    {{-- Header --}}
    @include('components.header')

    {{-- Main Content --}}
    <main class="pt-14 pb-16">
        @yield('content')
    </main>

    {{-- Floating Ying Character --}}
    @unless(request()->is('chat'))
    <div id="ying-float" class="fixed z-40" style="bottom: 5rem; right: 0.75rem;">
        {{-- Sound toggle --}}
        <button id="ying-sound-toggle" onclick="toggleYingSound()"
                class="absolute -top-2 -left-2 w-6 h-6 rounded-full bg-slate-800 border border-slate-600 flex items-center justify-center text-[10px] z-50 shadow-md hover:bg-slate-700 transition-colors"
                title="เปิด/ปิดเสียงน้องหญิง">
            <span id="ying-sound-icon">🔇</span>
        </button>

        <a href="/chat" class="block relative">
            {{-- Speech bubble --}}
            <div id="ying-bubble" class="absolute -top-14 -left-40 w-44 bg-white text-gray-800 text-xs rounded-xl px-3 py-2 shadow-lg opacity-0 transition-opacity duration-500" style="pointer-events:none;">
                <span id="ying-bubble-text">สวัสดีค่ะ! ถามหญิงได้นะ</span>
                <div class="absolute bottom-0 right-4 translate-y-1/2 rotate-45 w-3 h-3 bg-white"></div>
            </div>
            <img src="/images/ying.png" alt="น้องหญิง" class="w-14 h-14 rounded-full shadow-lg border-2 border-orange-400 ying-float-anim"
                 onerror="this.parentElement.parentElement.style.display='none'">
        </a>
    </div>
    <style>
        @keyframes yingFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .ying-float-anim {
            animation: yingFloat 3s ease-in-out infinite;
        }
    </style>
    <script>
        // ─── Ying Sound System ───
        const YING_SOUND_KEY = 'ying_sound_enabled';
        const YING_GREETED_KEY = 'ying_greeted_date';

        function isYingSoundEnabled() {
            return localStorage.getItem(YING_SOUND_KEY) === '1';
        }

        function toggleYingSound() {
            const enabled = !isYingSoundEnabled();
            localStorage.setItem(YING_SOUND_KEY, enabled ? '1' : '0');
            updateSoundIcon();

            if (enabled) {
                // First time enabling — greet immediately
                yingSpeak('เปิดเสียงแล้วค่ะ หญิงพร้อมช่วยเลยนะคะ 😊');
            }
        }

        function updateSoundIcon() {
            const icon = document.getElementById('ying-sound-icon');
            const btn = document.getElementById('ying-sound-toggle');
            if (isYingSoundEnabled()) {
                icon.textContent = '🔊';
                btn.classList.add('ring-2', 'ring-orange-500/50');
                btn.title = 'ปิดเสียงน้องหญิง';
            } else {
                icon.textContent = '🔇';
                btn.classList.remove('ring-2', 'ring-orange-500/50');
                btn.title = 'เปิดเสียงน้องหญิง';
            }
        }

        // Speak with quota management
        // force=true: always speak (daily greeting uses free browser TTS)
        // force=false: only if sound enabled (uses server TTS with quota)
        function yingSpeak(text, force = false) {
            if (!force && !isYingSoundEnabled()) return;

            if (force) {
                // Daily greeting: use free browser TTS to save quota
                if (window.sayTextBrowser) {
                    window.sayTextBrowser(text);
                } else if ('speechSynthesis' in window) {
                    const u = new SpeechSynthesisUtterance(text);
                    u.lang = 'th-TH';
                    u.pitch = 1.4;
                    u.rate = 1.05;
                    window.speechSynthesis.speak(u);
                }
            } else if (window.sayText) {
                window.sayText(text);
            }
        }

        // ─── Daily Greeting ───
        function shouldGreetToday() {
            const today = new Date().toDateString();
            const lastGreeted = localStorage.getItem(YING_GREETED_KEY);
            return lastGreeted !== today;
        }

        function markGreetedToday() {
            localStorage.setItem(YING_GREETED_KEY, new Date().toDateString());
        }

        // Greetings based on time of day
        function getDailyGreeting() {
            const hour = new Date().getHours();
            const user = '{{ auth()->user()?->nickname ?? "" }}';
            const name = user ? ` คุณ${user}` : '';

            if (hour < 6) return `ยังไม่นอนเหรอคะ${name}? หญิงห่วงนะคะ 🌙`;
            if (hour < 12) return `สวัสดีตอนเช้าค่ะ${name}! วันนี้เดินทางปลอดภัยนะคะ ☀️`;
            if (hour < 17) return `สวัสดีตอนบ่ายค่ะ${name}! มีอะไรให้หญิงช่วยไหมคะ? 😊`;
            if (hour < 21) return `สวัสดีตอนเย็นค่ะ${name}! ขับรถกลับบ้านระวังด้วยนะคะ 🌇`;
            return `สวัสดีค่ะ${name}! ดึกแล้วขับรถระวังนะคะ 🌙`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateSoundIcon();

            const bubble = document.getElementById('ying-bubble');
            const bubbleText = document.getElementById('ying-bubble-text');

            if (shouldGreetToday()) {
                const greeting = getDailyGreeting();

                if (bubbleText) bubbleText.textContent = greeting;

                // Show bubble
                if (bubble) {
                    setTimeout(() => { bubble.style.opacity = '1'; }, 1500);
                    setTimeout(() => { bubble.style.opacity = '0'; }, 7000);
                }

                // Always speak daily greeting (uses free browser TTS)
                setTimeout(() => { yingSpeak(greeting, true); }, 2000);

                markGreetedToday();
            } else {
                // Already greeted today — just show brief bubble
                if (bubble) {
                    setTimeout(() => { bubble.style.opacity = '1'; }, 2000);
                    setTimeout(() => { bubble.style.opacity = '0'; }, 4500);
                }
            }
        });
    </script>
    @endunless

    {{-- Bottom Navigation --}}
    @include('components.bottom-nav')

    {{-- Version --}}
    @php
        $version = 'dev';
        $versionFile = base_path('version.txt');
        if (file_exists($versionFile)) {
            $version = trim(file_get_contents($versionFile));
        }
    @endphp
    <div class="fixed bottom-16 right-2 z-40 text-[9px] text-slate-600 opacity-50">{{ $version }}</div>

    {{-- GPS Enforcement --}}
    <div id="gps-banner" style="display:none;"
         class="fixed top-0 left-0 right-0 z-[999] bg-red-600 text-white px-4 py-3 text-center shadow-xl">
        <div class="flex items-center justify-center gap-2">
            <span class="text-lg">📍</span>
            <span class="text-sm font-medium">กรุณาเปิด GPS เพื่อใช้งาน ThaiHelp</span>
            <button onclick="requestGPS()" class="ml-2 bg-white text-red-600 px-3 py-1 rounded-full text-xs font-bold">เปิด GPS</button>
            <button onclick="this.parentElement.parentElement.style.display='none'" class="ml-1 text-white/70 hover:text-white text-lg">&times;</button>
        </div>
    </div>
    <script>
        let _gpsGranted = false;
        function requestGPS() {
            if (!navigator.geolocation) {
                alert('เบราว์เซอร์ไม่รองรับ GPS');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    _gpsGranted = true;
                    window._userLat = pos.coords.latitude;
                    window._userLng = pos.coords.longitude;
                    document.getElementById('gps-banner').style.display = 'none';
                },
                (err) => {
                    if (err.code === 1) {
                        alert('คุณปฏิเสธการเข้าถึง GPS\nกรุณาเปิดในตั้งค่าเบราว์เซอร์');
                    }
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

        // Check GPS on load
        document.addEventListener('DOMContentLoaded', () => {
            if (!navigator.geolocation) return;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    _gpsGranted = true;
                    window._userLat = pos.coords.latitude;
                    window._userLng = pos.coords.longitude;
                },
                () => {
                    // GPS denied or unavailable — show banner
                    document.getElementById('gps-banner').style.display = 'block';

                    // Remind every 2 minutes
                    setInterval(() => {
                        if (!_gpsGranted) {
                            document.getElementById('gps-banner').style.display = 'block';
                        }
                    }, 120000);
                },
                { timeout: 5000 }
            );
        });
    </script>

    {{-- iOS/Android PWA Install Prompt + Permission Requests --}}
    <div id="pwa-install-banner" style="display:none;"
         class="fixed bottom-20 left-3 right-3 z-[998] metal-panel rounded-xl p-3 shadow-2xl border border-orange-500/30">
        <div class="flex items-center gap-3">
            <img src="/images/logo.png" class="w-10 h-10 rounded-xl" alt="ThaiHelp">
            <div class="flex-1">
                <p class="text-sm font-medium text-white">ติดตั้ง ThaiHelp</p>
                <p class="text-[10px] text-slate-400">เพิ่มลงหน้าจอเพื่อใช้งานสะดวกขึ้น</p>
            </div>
            <button id="pwa-install-btn" onclick="installPWA()" class="metal-btn-accent px-3 py-1.5 rounded-lg text-xs font-bold text-white">ติดตั้ง</button>
            <button onclick="dismissPWA()" class="text-slate-500 hover:text-white text-lg">&times;</button>
        </div>
        {{-- iOS Safari instructions --}}
        <div id="ios-install-hint" style="display:none;" class="mt-2 text-[11px] text-slate-400 bg-slate-800 rounded-lg p-2">
            📱 กด <strong class="text-white">แชร์</strong> (ปุ่ม ⬆️ ด้านล่าง) → แล้วกด <strong class="text-white">"เพิ่มไปยังหน้าจอโฮม"</strong>
        </div>
    </div>
    <script>
        let _deferredPrompt = null;

        // Android/Chrome: capture beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            _deferredPrompt = e;
            if (!localStorage.getItem('pwa_dismissed')) {
                document.getElementById('pwa-install-banner').style.display = 'block';
            }
        });

        function installPWA() {
            if (_deferredPrompt) {
                _deferredPrompt.prompt();
                _deferredPrompt.userChoice.then((choice) => {
                    if (choice.outcome === 'accepted') {
                        document.getElementById('pwa-install-banner').style.display = 'none';
                        // Track PWA install
                        trackPWAInstall('android');
                    }
                    _deferredPrompt = null;
                });
            }
        }

        function trackPWAInstall(device) {
            fetch('/api/pwa/installed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ device_type: device }),
            }).catch(() => {});
        }

        // Detect if running as PWA
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
            const ua = navigator.userAgent.toLowerCase();
            const device = /iphone|ipad/.test(ua) ? 'ios' : /android/.test(ua) ? 'android' : 'desktop';
            trackPWAInstall(device);
        }

        // Heartbeat — track active users
        @auth
        setInterval(() => {
            fetch('/api/heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            }).catch(() => {});
        }, 300000); // Every 5 minutes
        @endauth

        function dismissPWA() {
            document.getElementById('pwa-install-banner').style.display = 'none';
            localStorage.setItem('pwa_dismissed', Date.now());
        }

        // iOS Safari detection
        document.addEventListener('DOMContentLoaded', () => {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
            const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

            if (isIOS && !isStandalone && isSafari && !localStorage.getItem('pwa_dismissed')) {
                document.getElementById('pwa-install-banner').style.display = 'block';
                document.getElementById('ios-install-hint').style.display = 'block';
                document.getElementById('pwa-install-btn').style.display = 'none';
            }

            // iOS: request microphone + geolocation permissions proactively
            if (isIOS) {
                requestIOSPermissions();
            }
        });

        async function requestIOSPermissions() {
            if (localStorage.getItem('ios_permissions_asked')) return;

            // Wait a bit after page load
            setTimeout(async () => {
                try {
                    // Request microphone
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        stream.getTracks().forEach(t => t.stop()); // Release immediately
                    }
                } catch (e) {
                    console.log('[iOS] Mic permission:', e.message);
                }

                // GPS is requested by the GPS enforcement script already
                localStorage.setItem('ios_permissions_asked', '1');
            }, 3000);
        }
    </script>

    {{-- Vite JS --}}
    @vite(['resources/js/app.js'])

    {{-- Livewire --}}
    @livewireScripts

    {{-- Page Scripts --}}
    @stack('scripts')
</body>
</html>
