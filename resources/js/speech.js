/**
 * ThaiHelp - Voice / Speech Utilities
 * เสียงน้องหญิง - สาวน่ารัก ภาษาไทย
 */

let recognition = null;
let isListening = false;
let cachedVoice = null;

window.onSpeechResult = null;
window.onSpeechError = null;

/**
 * Find the best Thai female voice
 */
function findThaiVoice() {
    if (cachedVoice) return cachedVoice;

    const voices = window.speechSynthesis.getVoices();
    if (!voices.length) return null;

    // Priority: Thai female > Thai any > any female
    const thaiVoices = voices.filter(v => v.lang.startsWith('th'));

    // Prefer female voice (common names: Kanya, Niwat, female indicators)
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
    };

    recognition.onstart = () => {
        isListening = true;
    };

    recognition.start();
}

/**
 * Stop listening
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
 */
function sayText(text, options = {}) {
    if (!('speechSynthesis' in window) || !text) return null;

    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'th-TH';
    utterance.rate = options.rate || 1.05;   // slightly faster = more lively
    utterance.pitch = options.pitch || 1.4;  // higher pitch = younger/cuter
    utterance.volume = options.volume !== undefined ? options.volume : 1;

    const voice = findThaiVoice();
    if (voice) utterance.voice = voice;

    utterance.onend = () => {
        if (options.onEnd) options.onEnd();
    };

    utterance.onerror = (event) => {
        console.error('[Speech] TTS error:', event.error);
    };

    window.speechSynthesis.speak(utterance);
    return utterance;
}

function isSpeechListening() { return isListening; }
function isSpeaking() { return window.speechSynthesis?.speaking || false; }

// Export globally
window.startListening = startListening;
window.stopListening = stopListening;
window.sayText = sayText;
window.isSpeechListening = isSpeechListening;
window.isSpeaking = isSpeaking;

// Preload voices
if ('speechSynthesis' in window) {
    window.speechSynthesis.getVoices();
    window.speechSynthesis.onvoiceschanged = () => {
        cachedVoice = null;
        findThaiVoice();
    };
}
