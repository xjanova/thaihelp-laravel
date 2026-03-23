@extends('layouts.app')

@section('content')
<div class="flex flex-col" style="height: calc(100vh - 7.5rem);" x-data="chatApp()">
    {{-- Chat Messages --}}
    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-3" id="chat-messages" x-ref="messages">
        {{-- Welcome Message --}}
        <div class="flex gap-2 items-start">
            <div class="w-8 h-8 rounded-full overflow-hidden ring-2 ring-orange-500/50 flex-shrink-0">
                <img src="/images/ying.png" alt="น้องหญิง" class="w-full h-full object-cover">
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
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-sm flex-shrink-0">
                            👧
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
                <img src="/images/ying.png" alt="น้องหญิง" class="w-full h-full object-cover">
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
        return {
            messages: [],
            input: '',
            isTyping: false,
            isRecording: false,
            wakeWordActive: false,

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
                            history: this.messages.slice(-10),
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
                } catch (err) {
                    this.messages.push({
                        role: 'assistant',
                        content: 'ขอโทษค่ะ เกิดข้อผิดพลาด ลองใหม่อีกครั้งนะคะ 😢',
                        time: this.formatTime(),
                    });
                } finally {
                    this.isTyping = false;
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

            init() {
                // Auto-enable wake word on chat page
                this.enableWakeWord();
            }
        };
    }
</script>
@endpush
