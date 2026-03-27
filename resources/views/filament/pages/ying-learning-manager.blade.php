<x-filament-panels::page>
    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-500">{{ $stats['total'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">ข้อมูลทั้งหมด</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-warning-500">{{ $stats['pending'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">รออนุมัติ</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-success-500">{{ $stats['approved'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">อนุมัติแล้ว</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-info-500">{{ $stats['exported'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Export แล้ว</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-400">{{ number_format($stats['avg_quality'] ?? 0, 1) }}</div>
                <div class="text-xs text-gray-500">คะแนนเฉลี่ย</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: @entangle('activeTab') }" class="space-y-4">
        <div class="flex gap-2 border-b border-gray-700 pb-2">
            <button @click="tab = 'training'" :class="tab === 'training' ? 'bg-primary-500 text-white' : 'bg-gray-800 text-gray-400'" class="px-4 py-2 rounded-lg text-sm font-medium transition">
                ข้อมูลเทรน ({{ $stats['pending'] ?? 0 }} รอ)
            </button>
            <button @click="tab = 'memories'" :class="tab === 'memories' ? 'bg-primary-500 text-white' : 'bg-gray-800 text-gray-400'" class="px-4 py-2 rounded-lg text-sm font-medium transition">
                ความจำ ({{ count($memories) }})
            </button>
            <button @click="tab = 'patterns'" :class="tab === 'patterns' ? 'bg-primary-500 text-white' : 'bg-gray-800 text-gray-400'" class="px-4 py-2 rounded-lg text-sm font-medium transition">
                พฤติกรรม ({{ count($patterns) }})
            </button>
            <button @click="tab = 'config'" :class="tab === 'config' ? 'bg-primary-500 text-white' : 'bg-gray-800 text-gray-400'" class="px-4 py-2 rounded-lg text-sm font-medium transition">
                ตั้งค่า
            </button>
            <button @click="tab = 'export'" :class="tab === 'export' ? 'bg-primary-500 text-white' : 'bg-gray-800 text-gray-400'" class="px-4 py-2 rounded-lg text-sm font-medium transition">
                Export / HuggingFace
            </button>
        </div>

        {{-- Training Data Tab --}}
        <div x-show="tab === 'training'" x-cloak>
            <div class="flex gap-2 mb-4">
                <x-filament::button wire:click="bulkApprove" color="success" size="sm">
                    อนุมัติทั้งหมด (คะแนน 3)
                </x-filament::button>
            </div>

            <div class="space-y-3">
                @forelse($pendingData as $item)
                <x-filament::section>
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <span class="text-xs px-2 py-0.5 rounded bg-warning-500/20 text-warning-400">{{ $item['category'] ?? 'general' }}</span>
                            <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($item['created_at'])->diffForHumans() }}</span>
                        </div>
                        <div class="bg-gray-800/50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">ผู้ใช้:</p>
                            <p class="text-sm text-white">{{ \Illuminate\Support\Str::limit($item['user_message'], 200) }}</p>
                        </div>
                        <div class="bg-gray-800/50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">น้องหญิง:</p>
                            <p class="text-sm text-orange-300">{{ \Illuminate\Support\Str::limit($item['assistant_message'], 200) }}</p>
                        </div>
                        <div class="flex gap-2">
                            @for($q = 1; $q <= 5; $q++)
                            <x-filament::button wire:click="approveTraining({{ $item['id'] }}, {{ $q }})" color="success" size="xs">
                                {{ $q }}⭐
                            </x-filament::button>
                            @endfor
                            <x-filament::button wire:click="rejectTraining({{ $item['id'] }})" color="danger" size="xs">
                                ปฏิเสธ
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
                @empty
                <div class="text-center py-8 text-gray-500">ไม่มีข้อมูลรออนุมัติ</div>
                @endforelse
            </div>
        </div>

        {{-- Memories Tab --}}
        <div x-show="tab === 'memories'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="py-2 text-left">ผู้ใช้</th>
                            <th class="py-2 text-left">หมวด</th>
                            <th class="py-2 text-left">คีย์</th>
                            <th class="py-2 text-left">ค่า</th>
                            <th class="py-2 text-center">ใช้</th>
                            <th class="py-2 text-center">อนุมัติ</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($memories as $mem)
                        <tr>
                            <td class="py-2">{{ $mem['user']['nickname'] ?? $mem['user']['name'] ?? 'ไม่ระบุ' }}</td>
                            <td class="py-2">
                                <span class="text-xs px-2 py-0.5 rounded bg-blue-500/20 text-blue-400">{{ \App\Models\YingMemory::CATEGORIES[$mem['category']] ?? $mem['category'] }}</span>
                            </td>
                            <td class="py-2 text-gray-400">{{ $mem['key'] }}</td>
                            <td class="py-2 text-white">{{ \Illuminate\Support\Str::limit($mem['value'], 60) }}</td>
                            <td class="py-2 text-center text-gray-500">{{ $mem['use_count'] }}</td>
                            <td class="py-2 text-center">
                                <button wire:click="toggleMemoryApproval({{ $mem['id'] }})" class="text-lg">
                                    {{ $mem['admin_approved'] ? '✅' : '❌' }}
                                </button>
                            </td>
                            <td class="py-2">
                                <x-filament::button wire:click="deleteMemory({{ $mem['id'] }})" color="danger" size="xs">ลบ</x-filament::button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Patterns Tab --}}
        <div x-show="tab === 'patterns'" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="py-2 text-left">ผู้ใช้</th>
                            <th class="py-2 text-left">ประเภท</th>
                            <th class="py-2 text-left">คีย์</th>
                            <th class="py-2 text-left">ข้อมูล</th>
                            <th class="py-2 text-center">ครั้ง</th>
                            <th class="py-2 text-center">ความมั่นใจ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @foreach($patterns as $pat)
                        <tr>
                            <td class="py-2">{{ $pat['user']['nickname'] ?? $pat['user']['name'] ?? 'ไม่ระบุ' }}</td>
                            <td class="py-2">
                                <span class="text-xs px-2 py-0.5 rounded bg-purple-500/20 text-purple-400">{{ \App\Models\YingUserPattern::TYPES[$pat['pattern_type']] ?? $pat['pattern_type'] }}</span>
                            </td>
                            <td class="py-2 text-gray-400">{{ $pat['pattern_key'] }}</td>
                            <td class="py-2 text-white text-xs">{{ json_encode($pat['pattern_data'], JSON_UNESCAPED_UNICODE) }}</td>
                            <td class="py-2 text-center">{{ $pat['occurrence_count'] }}</td>
                            <td class="py-2 text-center">
                                @php $conf = $pat['confidence']; @endphp
                                <span class="{{ $conf >= 0.8 ? 'text-green-400' : ($conf >= 0.5 ? 'text-yellow-400' : 'text-red-400') }}">
                                    {{ number_format($conf * 100) }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Config Tab --}}
        <div x-show="tab === 'config'" x-cloak>
            <x-filament::section heading="ตั้งค่าระบบเรียนรู้">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-gray-300">ระบบหลัก</h3>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" wire:model.defer="learningEnabled" class="rounded">
                            <span class="text-sm">เปิดระบบเรียนรู้</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" wire:model.defer="autoCollect" class="rounded">
                            <span class="text-sm">เก็บข้อมูลเทรนอัตโนมัติ</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" wire:model.defer="memoryEnabled" class="rounded">
                            <span class="text-sm">เปิดระบบความจำผู้ใช้</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" wire:model.defer="behaviorTracking" class="rounded">
                            <span class="text-sm">ติดตามพฤติกรรมผู้ใช้</span>
                        </label>
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-gray-300">ค่าจำกัด</h3>
                        <div>
                            <label class="text-xs text-gray-400">ความจำสูงสุดต่อผู้ใช้</label>
                            <input type="number" wire:model.defer="memoryMaxPerUser" min="5" max="200" class="fi-input block w-full rounded-lg">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400">คะแนนขั้นต่ำสำหรับ Export (1-5)</label>
                            <input type="number" wire:model.defer="trainingMinQuality" min="1" max="5" class="fi-input block w-full rounded-lg">
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <x-filament::button wire:click="saveConfig" color="primary">บันทึกการตั้งค่า</x-filament::button>
                </div>
            </x-filament::section>
        </div>

        {{-- Export Tab --}}
        <div x-show="tab === 'export'" x-cloak>
            <x-filament::section heading="HuggingFace Integration">
                <div class="space-y-4">
                    <div>
                        <label class="text-xs text-gray-400">HuggingFace Dataset Repo ID (เช่น username/ying-training-data)</label>
                        <input type="text" wire:model.defer="huggingfaceRepo" placeholder="username/dataset-name" class="fi-input block w-full rounded-lg">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400">HuggingFace API Token</label>
                        <input type="password" wire:model.defer="huggingfaceToken" placeholder="hf_..." class="fi-input block w-full rounded-lg">
                    </div>
                    <div class="flex gap-3">
                        <x-filament::button wire:click="saveConfig" color="gray">บันทึก Token</x-filament::button>
                        <x-filament::button wire:click="exportJsonl" color="info">
                            Export เป็น JSONL
                        </x-filament::button>
                        <x-filament::button wire:click="pushToHuggingFace" color="success">
                            Push ไป HuggingFace
                        </x-filament::button>
                    </div>

                    <x-filament::section heading="รูปแบบข้อมูลที่ Export" collapsed>
                        <pre class="text-xs text-gray-400 bg-gray-900 rounded-lg p-3 overflow-x-auto">
{"messages":[{"role":"system","content":"..."},{"role":"user","content":"หาปั๊มใกล้สุด"},{"role":"assistant","content":"ปั๊ม PTT สุขุมวิท ใกล้สุดเลยค่ะ ห่างแค่ 1.2 กม. 🚗 นำทางไปเลยไหมคะ?"}],"category":"station","quality":4}
                        </pre>
                        <p class="text-xs text-gray-500 mt-2">
                            ไฟล์ JSONL สามารถนำไปใช้ fine-tune โมเดลบน HuggingFace ได้โดยตรง
                            รองรับ format ของ <code>trl</code> (Transformer Reinforcement Learning) library
                        </p>
                    </x-filament::section>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
