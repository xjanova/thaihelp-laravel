{{-- น้องหญิง Animated Avatar Component --}}
{{-- Usage: @include('components.nongying-avatar', ['size' => 'md']) --}}
{{-- Controlled via Alpine.js: x-data with isSpeaking, isListening --}}

@php
$sizeClass = match($size ?? 'md') {
    'sm' => 'w-12 h-12',
    'md' => 'w-20 h-20',
    'lg' => 'w-28 h-28',
    'xl' => 'w-36 h-36',
    default => 'w-20 h-20',
};
@endphp

<div class="nongying-avatar-container relative inline-block {{ $sizeClass }}"
     :class="{
        'nongying-speaking': typeof isSpeaking !== 'undefined' && isSpeaking,
        'nongying-listening': typeof isListening !== 'undefined' && isListening
     }">

    {{-- Glow ring --}}
    <div class="absolute inset-0 rounded-full opacity-0 transition-opacity duration-300"
         :class="{
            'opacity-100 animate-pulse': typeof isSpeaking !== 'undefined' && isSpeaking,
            'opacity-75': typeof isListening !== 'undefined' && isListening
         }"
         :style="(typeof isSpeaking !== 'undefined' && isSpeaking) ? 'box-shadow: 0 0 25px rgba(249, 115, 22, 0.5), 0 0 50px rgba(249, 115, 22, 0.2)' :
                 (typeof isListening !== 'undefined' && isListening) ? 'box-shadow: 0 0 25px rgba(239, 68, 68, 0.5), 0 0 50px rgba(239, 68, 68, 0.2)' : ''">
    </div>

    {{-- Avatar image with animations --}}
    <img src="/images/ying.webp"
         alt="น้องหญิง"
         class="nongying-img w-full h-full rounded-full object-cover border-2 relative z-10"
         :class="{
            'border-orange-400': typeof isSpeaking !== 'undefined' && isSpeaking,
            'border-red-400': typeof isListening !== 'undefined' && isListening,
            'border-slate-600': !(typeof isSpeaking !== 'undefined' && isSpeaking) && !(typeof isListening !== 'undefined' && isListening)
         }"
         style="filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));" />

    {{-- Status indicator --}}
    <div class="absolute -bottom-0.5 -right-0.5 z-20">
        <template x-if="typeof isListening !== 'undefined' && isListening">
            <div class="w-4 h-4 bg-red-500 rounded-full border-2 border-slate-900 animate-pulse"></div>
        </template>
        <template x-if="typeof isSpeaking !== 'undefined' && isSpeaking">
            <div class="w-4 h-4 bg-orange-500 rounded-full border-2 border-slate-900 flex items-center justify-center">
                <div class="flex gap-px">
                    <div class="w-0.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-0.5 h-2 bg-white rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-0.5 h-1.5 bg-white rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>
        </template>
        <template x-if="!(typeof isListening !== 'undefined' && isListening) && !(typeof isSpeaking !== 'undefined' && isSpeaking)">
            <div class="w-3 h-3 bg-emerald-500 rounded-full border-2 border-slate-900"></div>
        </template>
    </div>
</div>

<style>
/* Idle breathing animation */
.nongying-img {
    animation: nongying-breathe 3s ease-in-out infinite;
}

@keyframes nongying-breathe {
    0%, 100% { transform: scale(1) translateY(0); }
    50% { transform: scale(1.02) translateY(-2px); }
}

/* Speaking animation - gentle bounce + scale */
.nongying-speaking .nongying-img {
    animation: nongying-speak 0.4s ease-in-out infinite alternate;
}

@keyframes nongying-speak {
    0% { transform: scale(1) translateY(0); }
    100% { transform: scale(1.05) translateY(-3px); }
}

/* Listening animation - pulse gently */
.nongying-listening .nongying-img {
    animation: nongying-listen 1s ease-in-out infinite;
}

@keyframes nongying-listen {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.03); }
}

/* Eye blink simulation using overlay */
.nongying-avatar-container::after {
    content: '';
    position: absolute;
    top: 30%;
    left: 20%;
    right: 20%;
    height: 8%;
    background: transparent;
    z-index: 15;
    border-radius: 50%;
    animation: nongying-blink 4s ease-in-out infinite;
    pointer-events: none;
}

@keyframes nongying-blink {
    0%, 45%, 55%, 100% { opacity: 0; }
    48%, 52% { opacity: 0; }
}
</style>
