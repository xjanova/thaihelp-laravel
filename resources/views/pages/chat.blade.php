@extends('layouts.app')

@section('content')
<div class="flex flex-col" style="height: calc(100vh - 8rem); height: calc(100dvh - 8rem); padding-bottom: env(safe-area-inset-bottom, 0px);" x-data="chatApp()">
    {{-- Chat Messages --}}
    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3" id="chat-messages" x-ref="messages">
        {{-- Dynamic Messages (greeting is added by init() when messages is empty) --}}
        <template x-for="(msg, index) in messages" :key="index">
            <div>
                {{-- Assistant Message --}}
                <template x-if="msg.role === 'assistant'">
                    <div class="flex gap-2 items-start">
                        <div class="w-8 h-8 rounded-full overflow-hidden ring-2 ring-orange-500/50 flex-shrink-0">
                            <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover">
                        </div>
                        <div class="metal-panel rounded-2xl rounded-tl-sm px-3 py-2 max-w-[80%]">
                            <p class="text-sm text-slate-200 whitespace-pre-wrap" x-text="msg.content"></p>
                            <p class="text-[10px] text-slate-500 mt-1" x-text="msg.time"></p>
                        </div>
                    </div>
                </template>

                {{-- User Message --}}
                <template x-if="msg.role === 'user'">
                    <div class="flex gap-2 items-start justify-end">
                        <div class="rounded-2xl rounded-tr-sm px-3 py-2 max-w-[80%]" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                            <p class="text-sm text-white whitespace-pre-wrap" x-text="msg.content"></p>
                            <p class="text-[10px] text-orange-200/60 mt-1" x-text="msg.time"></p>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Typing Indicator --}}
        <div x-show="isTyping" class="flex gap-2 items-start">
            <div class="w-8 h-8 rounded-full overflow-hidden ring-2 ring-orange-500/50 flex-shrink-0">
                <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover">
            </div>
            <div class="metal-panel rounded-2xl rounded-tl-sm px-4 py-3">
                <div class="flex gap-1">
                    <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Input Bar --}}
    <div class="chrome-bar-bottom px-3 py-2 pb-20 flex-shrink-0" style="padding-bottom: max(5rem, calc(4rem + env(safe-area-inset-bottom, 0.75rem)))">
        <div class="flex items-center gap-2">
            {{-- Wake Word Toggle (hidden if speech not supported) --}}
            <button x-show="speechSupported" @click="wakeWordActive ? disableWakeWord() : enableWakeWord()"
                    :class="wakeWordActive ? 'bg-green-600 ring-2 ring-green-400/50' : 'metal-btn'"
                    class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 transition-all"
                    :title="wakeWordActive ? 'ปิด Wake Word' : 'เปิด Wake Word (พูดว่า น้องหญิง)'">
                <span class="text-sm" x-text="wakeWordActive ? '👂' : '🔇'"></span>
            </button>

            {{-- Mic Button (hidden if speech not supported on this device) --}}
            <button x-show="speechSupported" @click="toggleMic()" :class="isRecording ? 'metal-btn-accent glow-orange' : 'metal-btn'"
                    class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" :class="isRecording ? 'text-white' : 'text-slate-400'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4M12 15a3 3 0 003-3V5a3 3 0 00-6 0v7a3 3 0 003 3z"/>
                </svg>
            </button>

            {{-- Text Input --}}
            <div class="flex-1 relative">
                <input type="text" x-model="input" @keydown.enter="send()"
                       class="metal-input w-full px-4 py-2.5 rounded-xl text-sm text-white placeholder-slate-500 outline-none"
                       placeholder="พิมพ์ข้อความ..."
                       :disabled="isTyping">
            </div>

            {{-- TTS Toggle (default ON) --}}
            <button id="chat-tts-toggle" onclick="toggleChatTTS()"
                    class="w-10 h-10 rounded-full metal-btn flex items-center justify-center flex-shrink-0 text-sm"
                    title="เปิด/ปิดเสียงน้องหญิงในแชท">
                🔊
            </button>

            {{-- Send Button --}}
            <button @click="send()" :disabled="!input.trim() || isTyping"
                    :class="input.trim() && !isTyping ? 'metal-btn-accent' : 'metal-btn opacity-50'"
                    class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ─── Chat TTS (default OFF ประหยัดโควต้า) ───
    const CHAT_TTS_KEY = 'ying_chat_tts';

    function isChatTTSEnabled() {
        // Default ON — ผู้ใช้ปิดเองได้
        const val = localStorage.getItem(CHAT_TTS_KEY);
        return val === null || val === '1';
    }

    function toggleChatTTS() {
        const on = !isChatTTSEnabled();
        localStorage.setItem(CHAT_TTS_KEY, on ? '1' : '0');
        const btn = document.getElementById('chat-tts-toggle');
        if (btn) btn.textContent = on ? '🔊' : '🔇';
        if (on) window.sayText('เปิดเสียงในแชทแล้วค่ะ~');
    }

    // ─── TTS: ซอยข้อความเป็นประโยค → prefetch + เล่นต่อกัน ───
    let ttsAbort = null; // AbortController สำหรับหยุดพูดกลางคัน

    /**
     * ซอยข้อความยาวเป็นประโยคสั้นๆ (แยกตาม ค่ะ นะคะ เลยค่ะ ! ? หรือขึ้นบรรทัดใหม่)
     */
    function splitTtsChunks(text) {
        // แยกตามจุดจบประโยคภาษาไทย + อังกฤษ
        const parts = text.split(/(?<=ค่ะ|คะ|นะคะ|เลยค่ะ|ครับ|นะครับ|[!?。\n])\s*/);
        const chunks = [];
        let buf = '';

        for (const p of parts) {
            const trimmed = p.trim();
            if (!trimmed) continue;

            // รวมประโยคสั้นๆ เข้าด้วยกัน (< 30 ตัวอักษร)
            if (buf.length + trimmed.length < 80) {
                buf += (buf ? ' ' : '') + trimmed;
            } else {
                if (buf) chunks.push(buf);
                buf = trimmed;
            }
        }
        if (buf) chunks.push(buf);

        return chunks.length ? chunks : [text];
    }

    /**
     * Fetch TTS audio สำหรับ 1 chunk
     */
    async function fetchTtsAudio(text, signal) {
        const encoded = btoa(unescape(encodeURIComponent(text)));
        const res = await fetch('/api/tts?text=' + encodeURIComponent(encoded) + '&encoding=base64', { signal });

        if (!res.ok) throw new Error('TTS API ' + res.status);

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('audio')) throw new Error('Not audio');

        const blob = await res.blob();
        return URL.createObjectURL(blob);
    }

    /**
     * เล่น audio แล้ว return Promise ที่ resolve เมื่อเล่นจบ
     */
    function playAudioUrl(url) {
        return new Promise((resolve, reject) => {
            const audio = new Audio(url);
            audio.onended = () => { URL.revokeObjectURL(url); resolve(); };
            audio.onerror = () => { URL.revokeObjectURL(url); reject(new Error('play error')); };
            audio.play().catch(reject);
        });
    }

    /**
     * TTS หลัก — ซอย → prefetch → เล่นต่อกัน (chunk แรกเริ่มเร็วมาก)
     */
    window.sayText = async function(text) {
        if (!isChatTTSEnabled()) return;

        // หยุดเสียงก่อนหน้า
        if (ttsAbort) ttsAbort.abort();
        ttsAbort = new AbortController();
        const signal = ttsAbort.signal;

        // Strip action tags + emoji
        const clean = text
            .replace(/\[.*?\]/g, '')                                           // [FUEL_REPORT:...] etc.
            .replace(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F1E0}-\u{1F1FF}\u{2600}-\u{27BF}\u{FE00}-\u{FE0F}\u{1F900}-\u{1F9FF}\u{1FA00}-\u{1FA6F}\u{1FA70}-\u{1FAFF}\u{200D}\u{20E3}\u{E0020}-\u{E007F}]/gu, '')
            .replace(/\s{2,}/g, ' ')                                           // collapse extra spaces
            .trim();
        if (!clean) return;

        const chunks = splitTtsChunks(clean);
        console.log('[TTS] Split into', chunks.length, 'chunks:', chunks.map(c => c.substring(0, 30) + '...'));

        // Prefetch ทุก chunk พร้อมกัน
        const audioPromises = chunks.map(chunk => fetchTtsAudio(chunk, signal).catch(() => null));

        // เล่นทีละ chunk ตามลำดับ — chunk แรกเริ่มเร็วเพราะสั้น
        for (let i = 0; i < audioPromises.length; i++) {
            if (signal.aborted) return;

            try {
                const url = await audioPromises[i];
                if (!url || signal.aborted) continue;
                await playAudioUrl(url);
            } catch (e) {
                if (signal.aborted) return;
                console.log('[TTS] Chunk', i, 'failed, trying browser fallback');
                // Fallback สำหรับ chunk ที่ล้มเหลว
                await sayChunkBrowser(chunks[i]);
            }
        }
    };

    /**
     * Browser Web Speech fallback สำหรับ 1 chunk
     */
    function sayChunkBrowser(text) {
        return new Promise((resolve) => {
            if (!window.speechSynthesis) { resolve(); return; }

            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'th-TH';
            u.rate = 1.1;
            u.pitch = 1.4;

            const voices = window.speechSynthesis.getVoices();
            const thaiVoice = voices.find(v => v.lang === 'th-TH' && /female|premwadee|สาว/i.test(v.name))
                || voices.find(v => v.lang === 'th-TH')
                || voices.find(v => v.lang.startsWith('th'))
                || null;
            if (thaiVoice) u.voice = thaiVoice;

            u.onend = resolve;
            u.onerror = resolve;
            window.speechSynthesis.speak(u);
        });
    }

    // Preload voices
    if (window.speechSynthesis) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
    }

    function chatApp() {
        const MEMORY_KEY = 'ying_chat_history';
        const MEMORY_TTL = 8 * 60 * 60 * 1000; // 8 ชั่วโมง (ตลอดการเดินทาง)
        const MEMORY_WARN_AT = 7.5 * 60 * 60 * 1000; // เตือนที่ 7.5 ชม.
        const MAX_MEMORY_MSGS = 100; // จำได้สูงสุด 100 ข้อความ

        // Load saved conversation
        function loadMemory() {
            try {
                const saved = localStorage.getItem(MEMORY_KEY);
                if (!saved) return { messages: [], startedAt: Date.now(), warned: false };
                const data = JSON.parse(saved);
                const age = Date.now() - data.startedAt;

                // หมดอายุ → ลืมหมด
                if (age > MEMORY_TTL) {
                    localStorage.removeItem(MEMORY_KEY);
                    return { messages: [], startedAt: Date.now(), warned: false };
                }
                return data;
            } catch {
                return { messages: [], startedAt: Date.now(), warned: false };
            }
        }

        function saveMemory(messages, startedAt, warned) {
            try {
                // เก็บแค่ MAX_MEMORY_MSGS ล่าสุด
                const trimmed = messages.slice(-MAX_MEMORY_MSGS);
                localStorage.setItem(MEMORY_KEY, JSON.stringify({
                    messages: trimmed,
                    startedAt: startedAt,
                    warned: warned,
                }));
            } catch { /* localStorage full */ }
        }

        const memory = loadMemory();

        return {
            messages: memory.messages,
            input: '',
            isTyping: false,
            isRecording: false,
            wakeWordActive: false,
            speechSupported: true, // will be set in init()
            memoryStartedAt: memory.startedAt,
            memoryWarned: memory.warned,
            memoryTimer: null,

            _initialized: false,

            init() {
                // Guard against Alpine double-init (page transition / re-mount)
                if (this._initialized) return;
                this._initialized = true;

                // Check speech recognition support (iOS Safari may not have it)
                this.speechSupported = !!(window.SpeechRecognition || window.webkitSpeechRecognition);

                // Register fallback: if speech fails on iOS, focus text input
                window.onSpeechNotSupported = () => {
                    this.speechSupported = false;
                    this.isRecording = false;
                    // Focus text input as fallback
                    this.$nextTick(() => {
                        const inputEl = this.$el.querySelector('input[type="text"]');
                        if (inputEl) {
                            inputEl.focus();
                            inputEl.placeholder = 'พิมพ์ข้อความที่นี่ค่ะ (ไมค์ไม่พร้อมใช้งาน)';
                        }
                    });
                };

                this.scrollToBottom();

                // ถ้าเป็นครั้งแรก (ไม่มีข้อความเก่า) → ทักทาย (เช็คซ้ำว่ายังไม่มี greeting)
                if (this.messages.length === 0 && !this._greetingSent) {
                    this._greetingSent = true;
                    this.messages.push({
                        role: 'assistant',
                        content: 'สวัสดีค่ะ! หญิงเองค่ะ 😊 มีอะไรให้ช่วยไหมคะ?',
                        time: this.formatTime(),
                    });
                    this.saveChat();
                }

                // Memory expiry checker - ทุก 30 วินาที
                this.memoryTimer = setInterval(() => {
                    const age = Date.now() - this.memoryStartedAt;

                    // เตือนก่อนลืม (25 นาที)
                    if (age > MEMORY_WARN_AT && !this.memoryWarned && this.messages.length > 2) {
                        this.memoryWarned = true;
                        this.messages.push({
                            role: 'assistant',
                            content: '💭 หญิงกำลังจะลืมบทสนทนาแล้วนะคะ อีกสักครู่หญิงจะเริ่มใหม่ค่ะ ถ้ามีอะไรสำคัญบอกหญิงตอนนี้เลยนะคะ~',
                            time: this.formatTime(),
                        });
                        this.saveChat();
                        this.scrollToBottom();
                    }

                    // หมดเวลา → รีเซ็ต
                    if (age > MEMORY_TTL && this.messages.length > 0) {
                        this.messages = [];
                        this.memoryStartedAt = Date.now();
                        this.memoryWarned = false;
                        localStorage.removeItem(MEMORY_KEY);
                        this.messages.push({
                            role: 'assistant',
                            content: 'สวัสดีค่ะ! หญิงพร้อมช่วยใหม่แล้วนะคะ 😊 มีอะไรถามได้เลยค่ะ~',
                            time: this.formatTime(),
                        });
                        this.saveChat();
                        this.scrollToBottom();
                    }
                }, 30000);
            },

            destroy() {
                if (this.memoryTimer) clearInterval(this.memoryTimer);
            },

            saveChat() {
                saveMemory(this.messages, this.memoryStartedAt, this.memoryWarned);
            },

            formatTime() {
                return new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            async send() {
                const text = this.input.trim();
                if (!text || this.isTyping) return;

                // Prevent duplicate: check if last user message is the same
                const lastMsg = this.messages[this.messages.length - 1];
                if (lastMsg && lastMsg.role === 'user' && lastMsg.content === text) return;

                // Add user message
                this.messages.push({
                    role: 'user',
                    content: text,
                    time: this.formatTime(),
                });
                this.input = '';
                this.saveChat();
                this.scrollToBottom();

                // Show typing indicator
                this.isTyping = true;
                this.scrollToBottom();

                try {
                    // Encode Thai text as base64 to bypass Cloudflare WAF
                    const encodedMsg = btoa(unescape(encodeURIComponent(text)));
                    // Send history EXCLUDING the just-added user message (it's sent as 'message')
                    const historyMsgs = this.messages.filter(m => m.role && m.content).slice(0, -1).slice(-20);
                    const encodedHistory = historyMsgs.map(m => ({
                        role: m.role,
                        content: btoa(unescape(encodeURIComponent(m.content))),
                    }));

                    const response = await fetch('/api/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Encoding': 'base64',
                        },
                        body: JSON.stringify({
                            message: encodedMsg,
                            history: encodedHistory,
                            latitude: window._userLat || null,
                            longitude: window._userLng || null,
                            encoding: 'base64',
                        }),
                    });

                    let data;
                    try {
                        data = await response.json();
                    } catch (jsonErr) {
                        const rawText = await response.clone().text().catch(() => '');
                        console.error('[Chat] Non-JSON response:', response.status, rawText.substring(0, 200));
                        throw new Error('ขอโทษค่ะ เซิร์ฟเวอร์ตอบกลับผิดปกติค่ะ (HTTP ' + response.status + ')');
                    }

                    if (!response.ok) {
                        // Server returned error — but reply field may still have a friendly message
                        const errMsg = data?.reply
                            || data?.message
                            || (data?.errors ? JSON.stringify(data.errors).substring(0, 100) : null)
                            || 'ขอโทษค่ะ เซิร์ฟเวอร์มีปัญหาค่ะ (HTTP ' + response.status + ')';
                        console.error('[Chat] Server error:', response.status, data);
                        throw new Error(errMsg);
                    }

                    const reply = data.reply || 'ขอโทษค่ะ AI ตอบกลับว่างเปล่าค่ะ ลองใหม่นะคะ';

                    // Clean ALL command tags before displaying to user
                    // Use greedy catch-all: any [UPPERCASE_TAG...] pattern
                    const cleanReply = reply
                        .replace(/\[FUEL_REPORT:[^\]]*\]/gi, '')
                        .replace(/\[INCIDENT_REPORT:[^\]]*\]/gi, '')
                        .replace(/\[CONDITION:[^\]]*\]/gi, '')
                        .replace(/\[NAVIGATE:[^\]]*\]/gi, '')
                        .replace(/\[PLAY_VIDEO\]/gi, '')
                        .replace(/\[OPEN_STATIONS\]/gi, '')
                        .replace(/\[OPEN_TRIP\]/gi, '')
                        .replace(/\[OPEN_HOSPITALS\]/gi, '')
                        .replace(/\[CALL_SOS\]/gi, '')
                        .replace(/\[[A-Z_]{3,}(?::[^\]]*)?\]/g, '') // catch-all for any remaining command tags
                        .replace(/\s*ค่ะ\s*$/, ' ค่ะ')
                        .replace(/\s{2,}/g, ' ')
                        .trim();

                    this.messages.push({
                        role: 'assistant',
                        content: cleanReply,
                        time: this.formatTime(),
                    });

                    // Auto-play AI response with TTS (use clean version without tags)
                    if (window.sayText) {
                        window.sayText(cleanReply);
                    }

                    // Check for video replay command from AI
                    if (reply.includes('[PLAY_VIDEO]')) {
                        if (window.replayIntroVideo) {
                            const played = window.replayIntroVideo();
                            if (!played) {
                                // Already played too many times
                                this.messages.push({
                                    role: 'assistant',
                                    content: 'อายนะคะ 😳 พอแล้วๆ ดูหลายรอบแล้วนะ ไว้วันหลังนะคะ~',
                                    time: this.formatTime(),
                                });
                            }
                        }
                        // Remove the command tag from displayed message
                        this.messages[this.messages.length - 1].content =
                            this.messages[this.messages.length - 1].content.replace('[PLAY_VIDEO]', '').trim();
                    }

                    // Check for navigation command
                    const navMatch = reply.match(/\[NAVIGATE:(.*?)\]/);
                    if (navMatch) {
                        try {
                            const navData = JSON.parse(navMatch[1]);
                            let mapsUrl;
                            if (navData.lat && navData.lng) {
                                mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${navData.lat},${navData.lng}&travelmode=driving`;
                            } else if (navData.name) {
                                mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(navData.name)}&travelmode=driving`;
                            }
                            if (mapsUrl) {
                                window.open(mapsUrl, '_blank');
                            }
                        } catch (e) {
                            console.error('Navigate parse error:', e);
                        }
                        // Clean tag from message
                        const lastMsg2 = this.messages[this.messages.length - 1];
                        if (lastMsg2) lastMsg2.content = lastMsg2.content.replace(/\[NAVIGATE:.*?\]/g, '').trim();
                    }

                    // Check for fuel report in AI response — auto-submit to /api/stations/report
                    const reportMatch = reply.match(/\[FUEL_REPORT:([^\]]*)\]/);
                    if (reportMatch) {
                        try {
                            const reportData = JSON.parse(reportMatch[1]);
                            const lat = window._userLat;
                            const lng = window._userLng;
                            if (lat && lng) {
                                const stationName = (reportData.brand || 'ปั๊มน้ำมัน') + (reportData.branch ? ' ' + reportData.branch : '');
                                const fuelReports = [{
                                    fuel_type: reportData.fuel_type || 'diesel',
                                    status: reportData.status || 'available',
                                    price: reportData.price || null,
                                }];
                                const res = await fetch('/api/stations/report', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                                    body: JSON.stringify({
                                        placeId: 'ai_ying_' + Date.now(),
                                        stationName: stationName,
                                        fuelReports: fuelReports,
                                        note: 'รายงานผ่านน้องหญิง AI',
                                        latitude: lat,
                                        longitude: lng,
                                        source: 'ai_ying',
                                    }),
                                });
                                const result = await res.json().catch(() => null);
                                if (result?.success) {
                                    this.messages.push({ role: 'assistant', content: '✅ บันทึกรายงานน้ำมันเรียบร้อยค่ะ!', time: this.formatTime() });
                                    if (window.sayText) window.sayText('บันทึกเรียบร้อยค่ะ');
                                } else {
                                    this.messages.push({ role: 'assistant', content: '❌ บันทึกไม่สำเร็จค่ะ: ' + (result?.message || 'ลองใหม่นะคะ'), time: this.formatTime() });
                                }
                            } else {
                                this.messages.push({ role: 'assistant', content: '📍 ไม่สามารถระบุตำแหน่งได้ค่ะ กรุณาเปิด GPS แล้วลองใหม่นะคะ', time: this.formatTime() });
                            }
                        } catch (e) {
                            console.error('Failed to submit fuel report:', e);
                        }
                    }

                    // Check for incident report in AI response
                    const incidentMatch = reply.match(/\[INCIDENT_REPORT:([^\]]*)\]/);
                    if (incidentMatch) {
                        try {
                            const incidentData = JSON.parse(incidentMatch[1]);
                            const lat = window._userLat;
                            const lng = window._userLng;
                            if (lat && lng) {
                                const res = await fetch('/api/incidents', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                                    body: JSON.stringify({ category: incidentData.category || 'other', title: incidentData.title || 'รายงานจากน้องหญิง', description: incidentData.description || '', latitude: lat, longitude: lng, report_source: 'ai_ying' }),
                                });
                                const result = await res.json().catch(() => null);
                                this.messages.push({ role: 'assistant', content: result?.success ? '✅ บันทึกรายงานเหตุการณ์เรียบร้อยค่ะ!' : '❌ บันทึกไม่สำเร็จค่ะ ลองรายงานผ่านหน้า "แจ้งเหตุ" นะคะ', time: this.formatTime() });
                            } else {
                                this.messages.push({ role: 'assistant', content: '📍 ไม่สามารถระบุตำแหน่งได้ค่ะ กรุณาเปิด GPS แล้วลองใหม่นะคะ', time: this.formatTime() });
                            }
                        } catch (e) {
                            console.error('Failed to submit incident:', e);
                            this.messages.push({ role: 'assistant', content: '❌ เกิดข้อผิดพลาดในการบันทึกค่ะ ลองรายงานผ่านหน้า "แจ้งเหตุ" นะคะ', time: this.formatTime() });
                        }
                    }

                    // Handle app navigation commands with parameters
                    const stationsMatch = reply.match(/\[OPEN_STATIONS(?::(\{[^}]*\}))?\]/);
                    if (stationsMatch) {
                        let url = '/stations';
                        if (stationsMatch[1]) {
                            try {
                                const p = JSON.parse(stationsMatch[1]);
                                const params = new URLSearchParams();
                                if (p.brand) params.set('brand', p.brand);
                                if (p.fuel) params.set('fuel', p.fuel);
                                if (params.toString()) url += '?' + params.toString();
                            } catch {}
                        }
                        setTimeout(() => window.location.href = url, 1500);
                    }

                    const tripMatch = reply.match(/\[OPEN_TRIP(?::(\{[^}]*\}))?\]/);
                    if (tripMatch) {
                        let url = '/trip';
                        if (tripMatch[1]) {
                            try {
                                const p = JSON.parse(tripMatch[1]);
                                const params = new URLSearchParams();
                                if (p.from) params.set('from', p.from);
                                if (p.to) params.set('to', p.to);
                                if (params.toString()) url += '?' + params.toString();
                            } catch {}
                        }
                        setTimeout(() => window.location.href = url, 1500);
                    }

                    if (reply.includes('[OPEN_HOSPITALS]')) {
                        setTimeout(() => window.location.href = '/hospitals', 1500);
                    }
                    if (reply.includes('[CALL_SOS]')) {
                        if (window.triggerSOS) window.triggerSOS();
                    }

                    // Clean ALL command tags from displayed messages
                    const lastMsg = this.messages[this.messages.length - 1];
                    if (lastMsg && lastMsg.role === 'assistant') {
                        lastMsg.content = lastMsg.content
                            .replace(/\[FUEL_REPORT:[^\]]*\]/g, '')
                            .replace(/\[INCIDENT_REPORT:[^\]]*\]/g, '')
                            .replace(/\[CONDITION:[^\]]*\]/g, '')
                            .replace(/\[NAVIGATE:[^\]]*\]/g, '')
                            .replace(/\[REMEMBER:[^\]]*\]/g, '')
                            .replace(/\[PLAY_VIDEO\]/g, '')
                            .replace(/\[OPEN_STATIONS[^\]]*\]/g, '')
                            .replace(/\[OPEN_TRIP[^\]]*\]/g, '')
                            .replace(/\[OPEN_HOSPITALS\]/g, '')
                            .replace(/\[CALL_SOS\]/g, '')
                            .replace(/\[[A-Z_]{3,}[^\]]*\]/g, '') // catch-all
                            .replace(/\s{2,}/g, ' ')
                            .trim();
                    }
                } catch (err) {
                    console.error('[Chat] Error:', err.message);
                    this.messages.push({
                        role: 'assistant',
                        content: err.message || 'ขอโทษค่ะ เกิดข้อผิดพลาด ลองใหม่อีกครั้งนะคะ 😢',
                        time: this.formatTime(),
                    });
                } finally {
                    this.isTyping = false;
                    this.saveChat();
                    this.scrollToBottom();
                }
            },

            // ─── Mic button: tap to talk, auto-send when done ───
            toggleMic() {
                if (!this.isRecording) {
                    if (!window.startListening || !(window.SpeechRecognition || window.webkitSpeechRecognition)) {
                        this.speechSupported = false;
                        const inputEl = this.$el.querySelector('input[type="text"]');
                        if (inputEl) { inputEl.focus(); inputEl.placeholder = 'พิมพ์ข้อความที่นี่ (ไมค์ไม่พร้อมใช้งาน)'; }
                        return;
                    }

                    this.isRecording = true;
                    this.input = '';
                    let hasSent = false; // prevent double-send

                    window.startListening({
                        onResult: (transcript) => {
                            if (hasSent) return; // guard against duplicate final results
                            hasSent = true;
                            this.isRecording = false;
                            this.input = transcript;
                            // Auto-send immediately — user doesn't need to press send
                            this.$nextTick(() => this.send());
                        },
                        onInterim: (text) => {
                            // Show what user is saying (without ... to avoid sending it)
                            this.input = text;
                        },
                        onError: (err) => {
                            console.error('Speech error:', err);
                            this.isRecording = false;
                            if (err.message === 'speech_not_supported' || err.message === 'not-allowed') {
                                this.speechSupported = false;
                            }
                        }
                    });
                } else {
                    // User pressed mic again → stop recording
                    this.isRecording = false;
                    this.input = ''; // clear interim text
                    if (window.stopListening) window.stopListening();
                }
            },

            // ─── Wake Word mode: continuous conversation with น้องหญิง ───
            enableWakeWord() {
                this.wakeWordActive = true;

                window.onWakeWordDetected = () => {
                    if (this.isTyping) return; // don't interrupt while AI is responding
                    this.isRecording = true;
                    this.input = '';

                    // Listen for the actual command after wake word acknowledgment
                    setTimeout(() => {
                        let hasSent = false;
                        window.startListening({
                            onResult: async (command) => {
                                if (hasSent) return;
                                hasSent = true;
                                this.isRecording = false;
                                this.input = command;
                                await this.send();
                                // After AI responds, wake word listener auto-resumes via speech.js onend
                            },
                            onInterim: (text) => {
                                this.input = text;
                            },
                            onError: () => {
                                this.isRecording = false;
                            }
                        });
                    }, 800); // shorter delay — user is ready
                };

                if (window.startWakeWordListener) window.startWakeWordListener();
            },

            disableWakeWord() {
                this.wakeWordActive = false;
                window.onWakeWordDetected = null;
                if (window.stopWakeWordListener) window.stopWakeWordListener();
            },
        };
    }
</script>
@endpush
