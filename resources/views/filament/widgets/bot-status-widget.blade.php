@php
    $enabled = $this->getBotSetting()->enabled;
    $color = $enabled ? 'success' : 'danger';
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span
                    @class([
                        'flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl',
                        'bg-success-50 dark:bg-success-400/10' => $enabled,
                        'bg-danger-50 dark:bg-danger-400/10' => ! $enabled,
                    ])
                >
                    <x-filament::icon
                        :icon="$enabled ? 'heroicon-o-play-circle' : 'heroicon-o-pause-circle'"
                        @class([
                            'h-8 w-8',
                            'text-success-600 dark:text-success-400' => $enabled,
                            'text-danger-600 dark:text-danger-400' => ! $enabled,
                        ])
                    />
                </span>

                <div>
                    <div class="flex items-center gap-2">
                        <span @class([
                            'h-2 w-2 shrink-0 rounded-full',
                            'bg-success-500 animate-pulse' => $enabled,
                            'bg-danger-500' => ! $enabled,
                        ])></span>

                        <p class="text-xl font-bold leading-tight text-gray-950 dark:text-white">
                            {{ $enabled ? 'El bot está activo' : 'El bot está pausado' }}
                        </p>
                    </div>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        @if ($enabled)
                            Está respondiendo mensajes de WhatsApp automáticamente.
                        @else
                            Pausado desde {{ $this->getBotSetting()->paused_at?->diffForHumans() }}. Tus clientes no están recibiendo respuestas.
                        @endif
                    </p>
                </div>
            </div>

            {{ $this->toggleBotAction }}
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
