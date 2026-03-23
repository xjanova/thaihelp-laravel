<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            ระบบหมุนวน API Key อัตโนมัติ (Round-Robin) — กระจายการใช้งาน, ข้าม Key ที่ถูก rate limit, retry อัตโนมัติ
        </div>

        @foreach($this->pools as $service => $pool)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Header --}}
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $pool['label'] }}</h3>
                        <p class="text-xs text-gray-500">{{ $pool['count'] }} key(s) ในระบบ</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pool['count'] > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                        {{ $pool['count'] > 0 ? 'Active' : 'ยังไม่มี Key' }}
                    </span>
                </div>

                {{-- Keys List --}}
                @if(count($pool['keys']) > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($pool['keys'] as $idx => $key)
                            <div class="px-4 py-3 flex items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-sm text-gray-900 dark:text-white">{{ $key['label'] }}</span>
                                        @if($key['is_rate_limited'])
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">RATE LIMITED</span>
                                        @elseif($key['enabled'])
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">ACTIVE</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">DISABLED</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5 font-mono">{{ $key['key_preview'] }}</div>
                                    <div class="flex gap-4 mt-1 text-[11px] text-gray-400">
                                        <span>📊 ใช้วันนี้: {{ $key['usage_today'] }}</span>
                                        <span>⚠️ Errors: {{ $key['errors_today'] }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php
                                        $fullKey = collect(\App\Services\ApiKeyPool::getPool($service))->firstWhere('label', $key['label'])['key'] ?? '';
                                    @endphp
                                    <button
                                        wire:click="toggleKey('{{ $service }}', '{{ $fullKey }}', {{ $key['enabled'] ? 'false' : 'true' }})"
                                        class="text-xs px-2 py-1 rounded {{ $key['enabled'] ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}"
                                    >
                                        {{ $key['enabled'] ? 'ปิด' : 'เปิด' }}
                                    </button>
                                    <button
                                        wire:click="removeKey('{{ $service }}', '{{ $fullKey }}')"
                                        wire:confirm="ยืนยันลบ Key นี้?"
                                        class="text-xs px-2 py-1 rounded bg-red-100 text-red-800 hover:bg-red-200"
                                    >
                                        ลบ
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-4 py-6 text-center text-sm text-gray-400">
                        ยังไม่มี Key — ใช้ Key เดี่ยวจากหน้า Settings
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Info --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300">
            <strong>วิธีใช้:</strong>
            <ul class="mt-1 ml-4 list-disc space-y-1">
                <li>เพิ่ม Key หลายตัวต่อบริการ → ระบบหมุนวน Round-Robin อัตโนมัติ</li>
                <li>Key ที่โดน rate limit จะถูกข้ามชั่วคราว (1-5 นาที)</li>
                <li>ถ้าไม่เพิ่ม Key ในนี้ ระบบจะใช้ Key จากหน้า "ตั้งค่าระบบ" แทน</li>
                <li>สถิติ usage/errors reset ทุกวัน</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
