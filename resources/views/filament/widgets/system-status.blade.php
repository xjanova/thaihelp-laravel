<x-filament-widgets::widget>
    <x-filament::section heading="สถานะระบบ" icon="heroicon-o-server-stack">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($checks as $key => $check)
                <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <div @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                        'bg-green-100 dark:bg-green-900/30' => $check['status'],
                        'bg-red-100 dark:bg-red-900/30' => ! $check['status'],
                    ])>
                        @if ($check['status'])
                            <x-heroicon-s-check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                        @else
                            <x-heroicon-s-x-circle class="h-6 w-6 text-red-600 dark:text-red-400" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ $check['label'] }}
                        </p>
                        <p @class([
                            'text-xs',
                            'text-green-600 dark:text-green-400' => $check['status'],
                            'text-red-600 dark:text-red-400' => ! $check['status'],
                        ])>
                            {{ $check['detail'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
