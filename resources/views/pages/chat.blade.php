@extends('layouts.app')

@section('content')
<div class="flex flex-col" style="height: calc(100vh - 7.5rem);" x-data="chatApp()">
    {{-- Chat Messages --}}
    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3" id="chat-messages" x-ref="messages">
        {{-- Welcome Message --}}
        <div class="flex gap-2 items-start">
            <div class="w-8 h-8 rounded-full overflow-hidden ring-2 ring-orange-500/50 flex-shrink-0">
                <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover" onerror="this.src='/images/ying.png'">
            </div>
            <div class="metal-panel rounded-2xl rounded-tl-sm px-3 py-2 max-w-[80%]">
                <p class="text-sm text-slate-200">สวัสดีค่ะ! หญิงเองค่ะ 😊 มีอะไรให้ช่วยไหมคะ?</p>
                <p class="text-[10px] text-slate-500 mt-1">น้องหญิง AI</p>
            </div>
        </div>

        {{-- Dynamic Messages --}}
        <template x-for="(msg, index) in messages" :key="index">
            <div>
                {{-- Assistant Message --}}
                <template x-if="msg.role === 'assistant'">
                    <div class="flex gap-2 items-start">
                        <div class="w-8 h-8 rounded-full overflow-hidden ring-2 ring-orange-500/50 flex-shrink-0">
                            <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover" onerror="this.src='/images/ying.png'">
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
                <img src="/images/ying.webp" alt="น้องหญิง" class="w-full h-full object-cover" onerror="this.src='/images/ying.png'">
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
    <div class="chrome-bar-bottom px-3 py-2">
        <div class="flex items-center gap-2">
            {{-- Wake Word Toggle --}}
            <button @click="wakeWordActive ? disableWakeWord() : enableWakeWord()"
                    :class="wakeWordActive ? 'bg-green-600 ring-2 ring-green-400/50' : 'metal-btn'"
                    class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 transition-all"
                    :title="wakeWordActive ? 'ปิด Wake Word' : 'เปิด Wake Word (พูดว่า น้องหญิง)'">
                <span class="text-sm" x-text="wakeWordActive ? '👂' : '🔇'"></span>
            </button>

            {{-- Mic Button --}}
            <button @click="toggleMic()" :class="isRecording ? 'metal-btn-accent glow-orange' : 'metal-btn'"
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
            memoryStartedAt: memory.startedAt,
            memoryWarned: memory.warned,
            memoryTimer: null,

            init() {
                this.scrollToBottom();

                // ถ้าเป็นครั้งแรก (ไม่มีข้อความเก่า) → ทักทาย
                if (this.messages.length === 0) {
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
                    const response = await fetch('/api/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            message: text,
                            history: this.messages.filter(m => m.role && m.content).slice(-20).map(m => ({
                                role: m.role, content: m.content
                            })),
                            latitude: window._userLat || null,
                            longitude: window._userLng || null,
                        }),
                    });

                    if (!response.ok) throw new Error('Chat request failed');

                    const data = await response.json();

                    const reply = data.reply || 'ขอโทษค่ะ เกิดข้อผิดพลาด ลองใหม่นะคะ';
                    this.messages.push({
                        role: 'assistant',
                        content: reply,
                        time: this.formatTime(),
                    });

                    // Auto-play AI response with TTS
                    if (window.sayText) {
                        window.sayText(reply);
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

                    // Check for fuel report in AI response
                    const reportMatch = reply.match(/\[FUEL_REPORT:(.*?)\]/);
                    if (reportMatch) {
                        try {
                            const reportData = JSON.parse(reportMatch[1]);
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(async (pos) => {
                                    try {
                                        await fetch('/api/voice-command', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                            },
                                            body: JSON.stringify({
                                                transcript: text,
                                                latitude: pos.coords.latitude,
                                                longitude: pos.coords.longitude,
                                                fuel_report: reportData,
                                            }),
                                        });
                                    } catch (e) {
                                        console.error('Failed to submit fuel report:', e);
                                    }
                                });
                            }
                        } catch (e) {
                            console.error('Failed to parse fuel report:', e);
                        }
                    }

                    // Check for incident report in AI response
                    const incidentMatch = reply.match(/\[INCIDENT_REPORT:(.*?)\]/);
                    if (incidentMatch) {
                        try {
                            const incidentData = JSON.parse(incidentMatch[1]);
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(async (pos) => {
                                    try {
                                        await fetch('/api/incidents', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                            },
                                            body: JSON.stringify({
                                                category: incidentData.category || 'other',
                                                title: incidentData.title || 'รายงานจากน้องหญิง',
                                                description: incidentData.description || '',
                                                latitude: pos.coords.latitude,
                                                longitude: pos.coords.longitude,
                                            }),
                                        });
                                    } catch (e) {
                                        console.error('Failed to submit incident:', e);
                                    }
                                });
                            }
                        } catch (e) {
                            console.error('Failed to parse incident report:', e);
                        }
                    }

                    // Clean command tags from displayed messages
                    const lastMsg = this.messages[this.messages.length - 1];
                    if (lastMsg && lastMsg.role === 'assistant') {
                        lastMsg.content = lastMsg.content
                            .replace(/\[FUEL_REPORT:.*?\]/g, '')
                            .replace(/\[INCIDENT_REPORT:.*?\]/g, '')
                            .replace(/\[CONDITION:.*?\]/g, '')
                            .replace(/\[NAVIGATE:.*?\]/g, '')
                            .replace(/\[PLAY_VIDEO\]/g, '')
                            .trim();
                    }
                } catch (err) {
                    this.messages.push({
                        role: 'assistant',
                        content: 'ขอโทษค่ะ เกิดข้อผิดพลาด ลองใหม่อีกครั้งนะคะ 😢',
                        time: this.formatTime(),
                    });
                } finally {
                    this.isTyping = false;
                    this.saveChat();
                    this.scrollToBottom();
                }
            },

            toggleMic() {
                if (!this.isRecording) {
                    this.isRecording = true;
                    if (window.startListening) {
                        window.startListening({
                            onResult: (transcript) => {
                                this.input = transcript;
                                this.isRecording = false;
                                this.send();
                            },
                            onError: (err) => {
                                console.error('Speech error:', err);
                                this.isRecording = false;
                            }
                        });
                    }
                } else {
                    this.isRecording = false;
                    if (window.stopListening) {
                        window.stopListening();
                    }
                }
            },

            // Wake word - uses speech.js global wake word listener
            enableWakeWord() {
                this.wakeWordActive = true;

                // Set callback: when wake word detected, start recording
                window.onWakeWordDetected = () => {
                    this.isRecording = true;

                    // Listen for the actual command after wake word
                    setTimeout(() => {
                        window.startListening({
                            onResult: async (command) => {
                                this.isRecording = false;
                                this.input = command;
                                await this.send();
                            },
                            onInterim: (text) => {
                                this.input = text + '...';
                            },
                            onError: () => {
                                this.isRecording = false;
                            }
                        });
                    }, 1500);
                };

                if (window.startWakeWordListener) {
                    window.startWakeWordListener();
                }
            },

            disableWakeWord() {
                this.wakeWordActive = false;
                window.onWakeWordDetected = null;
                if (window.stopWakeWordListener) {
                    window.stopWakeWordListener();
                }
            },
        };
    }
</script>
@endpush
