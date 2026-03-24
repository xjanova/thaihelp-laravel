/**
 * ThaiHelp - Voice / Speech Utilities
 * เสียงน้องหญิง - สาวน่ารัก ภาษาไทย
 * Wake word: "น้องหญิง" or "หญิง"
 *
 * iOS Safari compatibility:
 * - Uses webkitSpeechRecognition (SpeechRecognition not available)
 * - Requires user gesture to start audio/mic
 * - continuous mode may not work — disabled on iOS
 * - Falls back to text input if speech recognition unavailable
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

// ─── iOS / Platform Detection ───────────────────────────
const _isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
const _isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
const _isIOSSafari = _isIOS && _isSafari;

/**
 * Check if SpeechRecognition API is available
 */
function getSpeechRecognitionClass() {
    return window.SpeechRecognition || window.webkitSpeechRecognition || null;
}

/**
 * Check if speech recognition is supported on this device
 */
function isSpeechSupported() {
    return !!getSpeechRecognitionClass();
}

// Expose for other scripts
window.isSpeechSupported = isSpeechSupported;

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
 * iOS Safari: webkitSpeechRecognition, no continuous mode, needs user gesture
 */
function startListening(options = {}) {
    const SpeechRecognitionClass = getSpeechRecognitionClass();

    if (!SpeechRecognitionClass) {
        console.warn('[Speech] SpeechRecognition not supported on this browser');
        const err = new Error('speech_not_supported');
        err.iosHint = _isIOS;
        if (options.onError) options.onError(err);
        if (window.onSpeechError) window.onSpeechError(err);

        // Notify chat to show text input fallback
        if (window.onSpeechNotSupported) window.onSpeechNotSupported();
        return;
    }

    if (isListening) stopListening();

    // Pause wake word listener while active listening
    if (isWakeListening) pauseWakeWord();

    recognition = new SpeechRecognitionClass();
    recognition.lang = 'th-TH';
    recognition.interimResults = true;
    recognition.maxAlternatives = 1;

    // iOS Safari does NOT support continuous mode reliably — disable it
    if (_isIOSSafari) {
        recognition.continuous = false;
    } else {
        recognition.continuous = options.continuous || false;
    }

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

        // On iOS, "not-allowed" often means no user gesture or permission denied
        if (_isIOS && (event.error === 'not-allowed' || event.error === 'service-not-allowed')) {
            console.warn('[Speech] iOS mic permission issue — showing text input fallback');
            if (window.onSpeechNotSupported) window.onSpeechNotSupported();
        }

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

    // iOS requires this to be called within a user gesture handler
    try {
        recognition.start();
    } catch (e) {
        console.error('[Speech] Failed to start recognition:', e.message);
        const err = new Error('speech_start_failed');
        if (options.onError) options.onError(err);
        if (window.onSpeechNotSupported) window.onSpeechNotSupported();
        isListening = false;
    }
}

/**
 * Stop active listening
 */
function stopListening() {
    if (recognition) {
        try { recognition.abort(); } catch (e) {}
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
 *
 * NOTE: On iOS Safari, continuous mode is unreliable.
 * We use a restart-loop instead: listen for a short burst, then restart.
 */
function startWakeWordListener() {
    const SpeechRecognitionClass = getSpeechRecognitionClass();
    if (!SpeechRecognitionClass) return;

    // Don't start if already active listening
    if (isListening) return;

    stopWakeWordListener();

    window._wakeWordEnabled = true;

    wakeRecognition = new SpeechRecognitionClass();
    wakeRecognition.lang = 'th-TH';
    wakeRecognition.interimResults = true;
    wakeRecognition.maxAlternatives = 3;

    // iOS Safari: continuous mode unreliable — use non-continuous + auto-restart
    if (_isIOSSafari) {
        wakeRecognition.continuous = false;
    } else {
        wakeRecognition.continuous = true;
    }

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
        // On iOS this fires after every short burst — restart loop
        if (window._wakeWordEnabled && !isListening) {
            setTimeout(() => startWakeWordListener(), _isIOSSafari ? 500 : 1000);
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
    sayText('ว่าไงคะ');

    // Trigger callback once after short delay (let TTS start first)
    setTimeout(() => {
        if (window.onWakeWordDetected) {
            window.onWakeWordDetected();
        }
    }, 500);
}

// ─── Exports ───────────────────────────────────────────

function isSpeechListening() { return isListening; }
function isSpeaking() { return window.speechSynthesis?.speaking || false; }
function isWakeWordActive() { return isWakeListening; }

window.startListening = startListening;
window.stopListening = stopListening;
window.sayText = sayText;
window.sayTextBrowser = sayTextBrowser;
window.isSpeechListening = isSpeechListening;
window.isSpeaking = isSpeaking;
window.isSpeechSupported = isSpeechSupported;
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
