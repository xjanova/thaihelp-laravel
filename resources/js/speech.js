/**
 * ThaiHelp - Voice / Speech Utilities
 *
 * Provides Web Speech API integration for Thai language
 * voice recognition and text-to-speech synthesis.
 */

let recognition = null;
let isListening = false;

// Callbacks (override these from your page scripts)
window.onSpeechResult = null;
window.onSpeechError = null;

/**
 * Start listening for voice input (Thai language)
 * @param {Object} options
 * @param {Function} options.onResult - Callback with transcript string
 * @param {Function} options.onError - Callback with error object
 * @param {boolean} options.continuous - Keep listening after result (default: false)
 */
function startListening(options = {}) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        const error = new Error('Speech recognition is not supported in this browser.');
        if (options.onError) options.onError(error);
        if (window.onSpeechError) window.onSpeechError(error);
        return;
    }

    if (isListening) {
        stopListening();
    }

    recognition = new SpeechRecognition();
    recognition.lang = 'th-TH';
    recognition.interimResults = false;
    recognition.continuous = options.continuous || false;
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
        const transcript = event.results[event.results.length - 1][0].transcript;
        const confidence = event.results[event.results.length - 1][0].confidence;

        console.log(`[Speech] Result: "${transcript}" (confidence: ${(confidence * 100).toFixed(1)}%)`);

        if (options.onResult) options.onResult(transcript, confidence);
        if (window.onSpeechResult) window.onSpeechResult(transcript, confidence);
    };

    recognition.onerror = (event) => {
        console.error('[Speech] Error:', event.error);

        const error = new Error(`Speech recognition error: ${event.error}`);
        if (options.onError) options.onError(error);
        if (window.onSpeechError) window.onSpeechError(error);

        isListening = false;
    };

    recognition.onend = () => {
        isListening = false;
        console.log('[Speech] Recognition ended');
    };

    recognition.onstart = () => {
        isListening = true;
        console.log('[Speech] Listening started...');
    };

    recognition.start();
}

/**
 * Stop listening for voice input
 */
function stopListening() {
    if (recognition) {
        recognition.abort();
        recognition = null;
    }
    isListening = false;
    console.log('[Speech] Listening stopped');
}

/**
 * Speak text aloud in Thai
 * @param {string} text - The text to speak
 * @param {Object} options
 * @param {number} options.rate - Speech rate (0.1-10, default: 1)
 * @param {number} options.pitch - Speech pitch (0-2, default: 1)
 * @param {number} options.volume - Volume (0-1, default: 1)
 * @param {Function} options.onEnd - Callback when speech finishes
 * @returns {SpeechSynthesisUtterance}
 */
function sayText(text, options = {}) {
    if (!('speechSynthesis' in window)) {
        console.error('[Speech] Speech synthesis not supported');
        return null;
    }

    // Cancel any ongoing speech
    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'th-TH';
    utterance.rate = options.rate || 1;
    utterance.pitch = options.pitch || 1;
    utterance.volume = options.volume !== undefined ? options.volume : 1;

    // Try to find a Thai voice
    const voices = window.speechSynthesis.getVoices();
    const thaiVoice = voices.find(v => v.lang.startsWith('th'));
    if (thaiVoice) {
        utterance.voice = thaiVoice;
    }

    utterance.onend = () => {
        console.log('[Speech] Finished speaking');
        if (options.onEnd) options.onEnd();
    };

    utterance.onerror = (event) => {
        console.error('[Speech] Synthesis error:', event.error);
    };

    window.speechSynthesis.speak(utterance);
    return utterance;
}

/**
 * Check if currently listening
 * @returns {boolean}
 */
function isSpeechListening() {
    return isListening;
}

/**
 * Check if currently speaking
 * @returns {boolean}
 */
function isSpeaking() {
    return window.speechSynthesis && window.speechSynthesis.speaking;
}

// Export for use in modules or global scope
window.startListening = startListening;
window.stopListening = stopListening;
window.sayText = sayText;
window.isSpeechListening = isSpeechListening;
window.isSpeaking = isSpeaking;

// Preload voices (some browsers need this)
if ('speechSynthesis' in window) {
    window.speechSynthesis.onvoiceschanged = () => {
        window.speechSynthesis.getVoices();
    };
}
