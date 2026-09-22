@php
    $enabled = $this->getBotSetting()->enabled;
@endphp

<x-filament-widgets::widget>
    <x-filament::section @class([
        'relative overflow-hidden transition-all duration-300',
        'ring-1 ring-primary-500/25 dark:ring-primary-500/35 bg-gradient-to-r from-primary-500/[0.04] via-primary-500/[0.01] to-transparent dark:from-primary-950/25' => $enabled,
        'ring-1 ring-gray-950/5 dark:ring-white/10 bg-gradient-to-r from-gray-500/[0.03] to-transparent dark:from-gray-900/30' => ! $enabled,
    ])>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                {{-- Status Icon Container --}}
                <div @class([
                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1 transition-transform duration-300',
                    'bg-primary-500/10 text-primary-600 ring-primary-500/20 dark:bg-primary-500/20 dark:text-primary-400' => $enabled,
                    'bg-gray-500/10 text-gray-500 ring-gray-950/5 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10' => ! $enabled,
                ])>
                    @if ($enabled)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>

                {{-- Status Details --}}
                <div>
                    <div class="flex items-center gap-2.5">
                        @if ($enabled)
                            <x-filament::badge color="success" size="sm">
                                <span class="relative flex h-1.5 w-1.5 mr-1">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                </span>
                                En línea
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="gray" size="sm">
                                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-gray-400 mr-1"></span>
                                Pausado
                            </x-filament::badge>
                        @endif

                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ $enabled ? 'El bot está activo' : 'El bot está en pausa' }}
                        </h2>
                    </div>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        @if ($enabled)
                            Respondiendo consultas de clientes y buscando productos automáticamente.
                        @else
                            Pausado {{ $this->getBotSetting()->paused_at ? 'desde '.$this->getBotSetting()->paused_at->diffForHumans() : 'manualmente' }}. Los clientes no están recibiendo respuestas.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Action Button --}}
            <div class="flex items-center sm:self-center">
                {{ $this->toggleBotAction }}
            </div>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
