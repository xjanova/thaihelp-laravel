<x-filament-panels::page>
    @php
        $cfg = $this->configValues;
        $configured = $this->isConfigured;
        $textChannels = $this->textChannels;
        $notifChannel = config('services.discord.notification_channel_id');
        $adminChannel = config('services.discord.admin_channel_id');
    @endphp

    {{-- Header Actions --}}
    <div class="flex items-center justify-end gap-3 mb-4">
        <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="refreshData" wire:loading.attr="disabled">
            รีเฟรชข้อมูล
        </x-filament::button>
    </div>

    {{-- Status Overview --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- ── Connection Status Card ─────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center gap-3">
                <div @class([
                    'flex h-10 w-10 items-center justify-center rounded-lg',
                    'bg-emerald-50 dark:bg-emerald-500/10' => $configured,
                    'bg-red-50 dark:bg-red-500/10' => !$configured,
                ])>
                    @if($configured)
                        <x-heroicon-o-check-circle class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    @else
                        <x-heroicon-o-x-circle class="h-6 w-6 text-red-600 dark:text-red-400" />
                    @endif
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">สถานะการเชื่อมต่อ</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $configured ? 'Bot พร้อมใช้งาน' : 'ยังไม่ได้ตั้งค่า Bot Token' }}
                    </p>
                </div>
            </div>

            <div class="space-y-3">
                {{-- Bot Token --}}
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Bot Token</span>
                    <div class="flex items-center gap-2">
                        @if($cfg['bot_token'])
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="text-sm text-emerald-600 dark:text-emerald-400">ตั้งค่าแล้ว</span>
                        @else
                            <span class="inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-sm text-red-600 dark:text-red-400">ยังไม่ได้ตั้งค่า</span>
                        @endif
                    </div>
                </div>

                {{-- Application ID --}}
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Application ID</span>
                    <span class="font-mono text-sm text-gray-900 dark:text-gray-100">
                        {{ $cfg['application_id'] ?: '—' }}
                    </span>
                </div>

                {{-- Guild ID --}}
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Guild ID</span>
                    <span class="font-mono text-sm text-gray-900 dark:text-gray-100">
                        {{ $cfg['guild_id'] ?: '—' }}
                    </span>
                </div>

                {{-- Notification Channel --}}
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Notification Channel</span>
                    <div class="flex items-center gap-2">
                        @if($cfg['notification_channel'])
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $cfg['notification_channel'] }}</span>
                        @else
                            <span class="inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-sm text-red-600 dark:text-red-400">ยังไม่ได้ตั้งค่า</span>
                        @endif
                    </div>
                </div>

                {{-- Admin Channel --}}
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Admin Channel</span>
                    <div class="flex items-center gap-2">
                        @if($cfg['admin_channel'])
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $cfg['admin_channel'] }}</span>
                        @else
                            <span class="inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-amber-600 dark:text-amber-400">ไม่ได้ตั้งค่า (ใช้ Notification Channel แทน)</span>
                        @endif
                    </div>
                </div>

                {{-- Webhook --}}
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Webhook URL</span>
                    <div class="flex items-center gap-2">
                        @if($cfg['webhook_set'])
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="font-mono text-xs text-gray-900 dark:text-gray-100">{{ $cfg['webhook_url'] }}</span>
                        @else
                            <span class="inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-amber-600 dark:text-amber-400">ไม่ได้ตั้งค่า</span>
                        @endif
                    </div>
                </div>

                {{-- Public Key --}}
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-2.5 dark:bg-gray-700/50">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Public Key (Interactions)</span>
                    <div class="flex items-center gap-2">
                        @if($cfg['public_key'])
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="text-sm text-emerald-600 dark:text-emerald-400">ตั้งค่าแล้ว</span>
                        @else
                            <span class="inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                            <span class="text-sm text-red-600 dark:text-red-400">ยังไม่ได้ตั้งค่า</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Bot Info Card ──────────────────────────────── --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-500/10">
                    <x-heroicon-o-cpu-chip class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">ข้อมูล Bot</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">รายละเอียดจาก Discord API</p>
                </div>
            </div>

            @if(!empty($this->botInfo))
                <div class="mb-6 flex items-center gap-4 rounded-lg border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
                    @if(!empty($this->botInfo['avatar']))
                        <img
                            src="https://cdn.discordapp.com/avatars/{{ $this->botInfo['id'] }}/{{ $this->botInfo['avatar'] }}.png?size=64"
                            alt="Bot Avatar"
                            class="h-14 w-14 rounded-full ring-2 ring-indigo-200 dark:ring-indigo-700"
                        />
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-200 dark:bg-indigo-700">
                            <x-heroicon-o-user class="h-8 w-8 text-indigo-600 dark:text-indigo-300" />
                        </div>
                    @endif
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $this->botInfo['username'] ?? 'Unknown' }}
                            @if(!empty($this->botInfo['discriminator']) && $this->botInfo['discriminator'] !== '0')
                                <span class="text-gray-400">#{{ $this->botInfo['discriminator'] }}</span>
                            @endif
                        </p>
                        <p class="font-mono text-xs text-gray-500 dark:text-gray-400">ID: {{ $this->botInfo['id'] ?? '—' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-700/50">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ count($this->commands) }}</p>
                        <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Slash Commands</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-700/50">
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ count($textChannels) }}</p>
                        <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Text Channels</p>
                    </div>
                </div>

                {{-- Commands List --}}
                @if(!empty($this->commands))
                    <div class="mt-4">
                        <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">คำสั่งที่ลงทะเบียนแล้ว</p>
                        <div class="space-y-1.5">
                            @foreach($this->commands as $cmd)
                                <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 dark:bg-gray-700/50">
                                    <span class="font-mono text-sm font-medium text-gray-900 dark:text-gray-100">/{{ $cmd['name'] ?? '?' }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $cmd['description'] ?? '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-200 py-12 dark:border-gray-700">
                    <x-heroicon-o-exclamation-triangle class="mb-3 h-10 w-10 text-gray-400" />
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $configured ? 'ไม่สามารถเชื่อมต่อ Discord API ได้' : 'กรุณาตั้งค่า Bot Token ก่อน' }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Quick Actions ──────────────────────────────────── --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-500/10">
                <x-heroicon-o-bolt class="h-6 w-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">จัดการและทดสอบ Discord Bot</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Test Message --}}
            <button
                wire:click="sendTestMessage"
                wire:loading.attr="disabled"
                @disabled(!$configured)
                @class([
                    'group relative flex items-center gap-3 rounded-lg border px-4 py-3 text-left transition-all',
                    'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-700/50 dark:hover:border-blue-600 dark:hover:bg-blue-900/20' => $configured,
                    'cursor-not-allowed border-gray-100 bg-gray-50 opacity-50 dark:border-gray-700 dark:bg-gray-800' => !$configured,
                ])
            >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <x-heroicon-o-chat-bubble-bottom-center-text class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">ทดสอบส่งข้อความ</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ส่งข้อความไปยัง Notification Channel</p>
                </div>
                <div wire:loading wire:target="sendTestMessage" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <x-filament::loading-indicator class="h-5 w-5 text-blue-500" />
                </div>
            </button>

            {{-- Admin Alert --}}
            <button
                wire:click="sendTestAdminAlert"
                wire:loading.attr="disabled"
                @disabled(!$configured)
                @class([
                    'group relative flex items-center gap-3 rounded-lg border px-4 py-3 text-left transition-all',
                    'border-gray-200 bg-white hover:border-red-300 hover:bg-red-50 dark:border-gray-600 dark:bg-gray-700/50 dark:hover:border-red-600 dark:hover:bg-red-900/20' => $configured,
                    'cursor-not-allowed border-gray-100 bg-gray-50 opacity-50 dark:border-gray-700 dark:bg-gray-800' => !$configured,
                ])
            >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                    <x-heroicon-o-bell-alert class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">ทดสอบ Admin Alert</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ส่ง Alert ไปยัง Admin Channel</p>
                </div>
                <div wire:loading wire:target="sendTestAdminAlert" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <x-filament::loading-indicator class="h-5 w-5 text-red-500" />
                </div>
            </button>

            {{-- Register Commands --}}
            <button
                wire:click="registerSlashCommands"
                wire:loading.attr="disabled"
                @disabled(!$configured)
                wire:confirm="ต้องการลงทะเบียน Slash Commands ใหม่ทั้งหมด?"
                @class([
                    'group relative flex items-center gap-3 rounded-lg border px-4 py-3 text-left transition-all',
                    'border-gray-200 bg-white hover:border-purple-300 hover:bg-purple-50 dark:border-gray-600 dark:bg-gray-700/50 dark:hover:border-purple-600 dark:hover:bg-purple-900/20' => $configured,
                    'cursor-not-allowed border-gray-100 bg-gray-50 opacity-50 dark:border-gray-700 dark:bg-gray-800' => !$configured,
                ])
            >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                    <x-heroicon-o-command-line class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">ลงทะเบียน Slash Commands</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">อัปเดตคำสั่งทั้งหมดใน Discord</p>
                </div>
                <div wire:loading wire:target="registerSlashCommands" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <x-filament::loading-indicator class="h-5 w-5 text-purple-500" />
                </div>
            </button>

            {{-- Set Interactions Endpoint --}}
            <button
                wire:click="setInteractionsEndpoint"
                wire:loading.attr="disabled"
                @disabled(!$configured)
                wire:confirm="ตั้ง Interactions Endpoint เป็น {{ rtrim(config('app.url'), '/') }}/discord/interactions ?"
                @class([
                    'group relative flex items-center gap-3 rounded-lg border px-4 py-3 text-left transition-all',
                    'border-gray-200 bg-white hover:border-emerald-300 hover:bg-emerald-50 dark:border-gray-600 dark:bg-gray-700/50 dark:hover:border-emerald-600 dark:hover:bg-emerald-900/20' => $configured,
                    'cursor-not-allowed border-gray-100 bg-gray-50 opacity-50 dark:border-gray-700 dark:bg-gray-800' => !$configured,
                ])
            >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    <x-heroicon-o-globe-alt class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">ตั้ง Interactions Endpoint</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ rtrim(config('app.url'), '/') }}/discord/interactions</p>
                </div>
                <div wire:loading wire:target="setInteractionsEndpoint" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <x-filament::loading-indicator class="h-5 w-5 text-emerald-500" />
                </div>
            </button>

            {{-- Create Webhook --}}
            <button
                wire:click="createWebhook"
                wire:loading.attr="disabled"
                @disabled(!$configured)
                wire:confirm="สร้าง Webhook ใหม่สำหรับ Notification Channel?"
                @class([
                    'group relative flex items-center gap-3 rounded-lg border px-4 py-3 text-left transition-all',
                    'border-gray-200 bg-white hover:border-amber-300 hover:bg-amber-50 dark:border-gray-600 dark:bg-gray-700/50 dark:hover:border-amber-600 dark:hover:bg-amber-900/20' => $configured,
                    'cursor-not-allowed border-gray-100 bg-gray-50 opacity-50 dark:border-gray-700 dark:bg-gray-800' => !$configured,
                ])
            >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <x-heroicon-o-link class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">สร้าง Webhook</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">สร้าง Webhook สำหรับ Notification Channel</p>
                </div>
                <div wire:loading wire:target="createWebhook" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <x-filament::loading-indicator class="h-5 w-5 text-amber-500" />
                </div>
            </button>
        </div>
    </div>

    {{-- ── Guild Channels ─────────────────────────────────── --}}
    @if(!empty($textChannels))
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-50 dark:bg-cyan-500/10">
                    <x-heroicon-o-hashtag class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Channels ใน Guild</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Text channels ทั้งหมดที่ Bot เข้าถึงได้</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($textChannels as $ch)
                    @php
                        $isNotif = ($ch['id'] ?? '') === $notifChannel;
                        $isAdmin = ($ch['id'] ?? '') === $adminChannel;
                    @endphp
                    <div @class([
                        'flex items-center justify-between rounded-lg px-4 py-2.5',
                        'bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800' => $isNotif || $isAdmin,
                        'bg-gray-50 dark:bg-gray-700/50' => !$isNotif && !$isAdmin,
                    ])>
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-gray-400 dark:text-gray-500">#</span>
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $ch['name'] ?? '—' }}</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5 ml-2">
                            @if($isNotif)
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    Notifications
                                </span>
                            @endif
                            @if($isAdmin)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                    Admin
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
