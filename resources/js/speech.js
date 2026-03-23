/**
 * ThaiHelp - Voice / Speech Utilities
 * เสียงน้องหญิง - สาวน่ารัก ภาษาไทย
 * Wake word: "น้องหญิง" or "หญิง"
 */

let recognition = null;
let wakeRecognition = null;
let isListening = false;
let isWakeListening = false;
let cachedVoice = null;

window.onSpeechResult = null;
window.onSpeechError = null;
window.onWakeWordDetected = null;

const WAKE_WORDS = ['น้องหญิง', 'หญิง', 'nong ying', 'ying'];

/**
 * Find the best Thai female voice
 */
function findThaiVoice() {
    if (cachedVoice) return cachedVoice;

    const voices = window.speechSynthesis.getVoices();
    if (!voices.length) return null;

    const thaiVoices = voices.filter(v => v.lang.startsWith('th'));

    cachedVoice = thaiVoices.find(v =>
        /female|kanya|สตรี|หญิง/i.test(v.name)
    ) || thaiVoices[0] || null;

    if (cachedVoice) {
        console.log('[Speech] Using voice:', cachedVoice.name, cachedVoice.lang);
    }

    return cachedVoice;
}

/**
 * Start listening for voice input (Thai)
 */
function startListening(options = {}) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        const err = new Error('เบราว์เซอร์ไม่รองรับการฟังเสียง');
        if (options.onError) options.onError(err);
        if (window.onSpeechError) window.onSpeechError(err);
        return;
    }

    if (isListening) stopListening();

    // Pause wake word listener while active listening
    if (isWakeListening) pauseWakeWord();

    recognition = new SpeechRecognition();
    recognition.lang = 'th-TH';
    recognition.interimResults = true;
    recognition.continuous = options.continuous || false;
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
        const result = event.results[event.results.length - 1];
        const transcript = result[0].transcript;
        const isFinal = result.isFinal;

        if (isFinal) {
            console.log(`[Speech] Final: "${transcript}"`);
            if (options.onResult) options.onResult(transcript, result[0].confidence);
            if (window.onSpeechResult) window.onSpeechResult(transcript, result[0].confidence);
        } else if (options.onInterim) {
            options.onInterim(transcript);
        }
    };

    recognition.onerror = (event) => {
        console.error('[Speech] Error:', event.error);
        if (event.error !== 'aborted') {
            const err = new Error(event.error);
            if (options.onError) options.onError(err);
            if (window.onSpeechError) window.onSpeechError(err);
        }
        isListening = false;
    };

    recognition.onend = () => {
        isListening = false;
        // Resume wake word listener after active listening ends
        if (!isWakeListening && window._wakeWordEnabled) {
            setTimeout(() => startWakeWordListener(), 500);
        }
    };

    recognition.onstart = () => {
        isListening = true;
    };

    recognition.start();
}

/**
 * Stop active listening
 */
function stopListening() {
    if (recognition) {
        recognition.abort();
        recognition = null;
    }
    isListening = false;
}

/**
 * Speak text as น้องหญิง (young female Thai voice)
 * Tries server-side TTS first (Google Cloud, real Thai female),
 * falls back to browser Web Speech API.
 */
function sayText(text, options = {}) {
    if (!text) return null;

    // Try server-side TTS first for real Thai female voice
    sayTextServer(text, options).catch(() => {
        // Fallback to browser TTS
        sayTextBrowser(text, options);
    });
}

/**
 * Server-side TTS via /api/tts (Google Cloud Thai female voice)
 */
async function sayTextServer(text, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const response = await fetch('/api/tts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
        },
        body: JSON.stringify({ text }),
    });

    if (!response.ok) throw new Error('TTS API failed');

    const contentType = response.headers.get('content-type');

    // If JSON response, it means fallback needed
    if (contentType?.includes('json')) {
        throw new Error('Server returned fallback signal');
    }

    // Got audio binary
    const blob = await response.blob();
    const audioUrl = URL.createObjectURL(blob);
    const audio = new Audio(audioUrl);
    audio.volume = options.volume !== undefined ? options.volume : 1;

    audio.onended = () => {
        URL.revokeObjectURL(audioUrl);
        if (options.onEnd) options.onEnd();
    };

    audio.onerror = () => {
        URL.revokeObjectURL(audioUrl);
        // Fallback to browser
        sayTextBrowser(text, options);
    };

    await audio.play();
    console.log('[TTS] Playing server-side Thai female voice');
}

/**
 * Browser Web Speech API fallback
 */
function sayTextBrowser(text, options = {}) {
    if (!('speechSynthesis' in window)) return;

    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'th-TH';
    utterance.rate = options.rate || 1.05;
    utterance.pitch = options.pitch || 1.4;
    utterance.volume = options.volume !== undefined ? options.volume : 1;

    const voice = findThaiVoice();
    if (voice) utterance.voice = voice;

    utterance.onend = () => {
        if (options.onEnd) options.onEnd();
    };

    utterance.onerror = (event) => {
        console.error('[Speech] Browser TTS error:', event.error);
    };

    window.speechSynthesis.speak(utterance);
    console.log('[TTS] Using browser fallback voice');
}

// ─── Wake Word Detection ───────────────────────────────

/**
 * Start background wake word listener.
 * Continuously listens for "น้องหญิง" or "หญิง".
 * When detected, calls window.onWakeWordDetected callback.
 */
function startWakeWordListener() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) return;

    // Don't start if already active listening
    if (isListening) return;

    stopWakeWordListener();

    window._wakeWordEnabled = true;

    wakeRecognition = new SpeechRecognition();
    wakeRecognition.lang = 'th-TH';
    wakeRecognition.interimResults = true;
    wakeRecognition.continuous = true;
    wakeRecognition.maxAlternatives = 3;

    wakeRecognition.onresult = (event) => {
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0].transcript.toLowerCase().trim();

            // Check all alternatives for wake word
            for (let j = 0; j < event.results[i].length; j++) {
                const alt = event.results[i][j].transcript.toLowerCase().trim();
                if (containsWakeWord(alt)) {
                    console.log('[Wake] Detected wake word in:', alt);
                    handleWakeWordDetected();
                    return;
                }
            }

            // Also check primary transcript
            if (containsWakeWord(transcript)) {
                console.log('[Wake] Detected wake word:', transcript);
                handleWakeWordDetected();
                return;
            }
        }
    };

    wakeRecognition.onerror = (event) => {
        if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
            console.warn('[Wake] Microphone permission denied');
            isWakeListening = false;
            return;
        }
        // For other errors, restart
        isWakeListening = false;
        if (window._wakeWordEnabled && !isListening) {
            setTimeout(() => startWakeWordListener(), 2000);
        }
    };

    wakeRecognition.onend = () => {
        isWakeListening = false;
        // Auto-restart if still enabled and not actively listening
        if (window._wakeWordEnabled && !isListening) {
            setTimeout(() => startWakeWordListener(), 1000);
        }
    };

    wakeRecognition.onstart = () => {
        isWakeListening = true;
        console.log('[Wake] Listening for wake word...');
    };

    try {
        wakeRecognition.start();
    } catch (e) {
        console.warn('[Wake] Could not start:', e.message);
    }
}

/**
 * Stop wake word listener
 */
function stopWakeWordListener() {
    window._wakeWordEnabled = false;
    if (wakeRecognition) {
        try { wakeRecognition.abort(); } catch (e) {}
        wakeRecognition = null;
    }
    isWakeListening = false;
}

/**
 * Pause wake word (while active listening)
 */
function pauseWakeWord() {
    if (wakeRecognition) {
        try { wakeRecognition.abort(); } catch (e) {}
        wakeRecognition = null;
    }
    isWakeListening = false;
}

/**
 * Check if text contains a wake word
 */
function containsWakeWord(text) {
    if (!text) return false;
    return WAKE_WORDS.some(w => text.includes(w));
}

/**
 * Handle wake word detected
 */
function handleWakeWordDetected() {
    // Stop wake listener first
    pauseWakeWord();

    // Play acknowledgment
    sayText('ว่าไงคะ หญิงพร้อมช่วยแล้วค่ะ', {
        onEnd: () => {
            // After speaking, trigger callback
            if (window.onWakeWordDetected) {
                window.onWakeWordDetected();
            }
        }
    });

    // Also trigger immediately (don't wait for TTS)
    setTimeout(() => {
        if (window.onWakeWordDetected) {
            window.onWakeWordDetected();
        }
    }, 300);
}

// ─── Exports ───────────────────────────────────────────

function isSpeechListening() { return isListening; }
function isSpeaking() { return window.speechSynthesis?.speaking || false; }
function isWakeWordActive() { return isWakeListening; }

window.startListening = startListening;
window.stopListening = stopListening;
window.sayText = sayText;
window.isSpeechListening = isSpeechListening;
window.isSpeaking = isSpeaking;
window.startWakeWordListener = startWakeWordListener;
window.stopWakeWordListener = stopWakeWordListener;
window.isWakeWordActive = isWakeWordActive;

// Preload voices
if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => {
        cachedVoice = null;
        findThaiVoice();
    };
}
