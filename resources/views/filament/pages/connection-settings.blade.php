@php
    $setting = $this->getSetting();
    $isConfigured = $setting->isConfigured();
    $isVerified = $setting->isVerified();
@endphp

<x-filament-panels::page>
    {{-- Header de Estado de la Integración --}}
    <x-filament::section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div @class([
                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1 transition-colors',
                    'bg-primary-500/10 text-primary-600 ring-primary-500/20 dark:bg-primary-500/20 dark:text-primary-400' => $isConfigured,
                    'bg-gray-500/10 text-gray-500 ring-gray-950/5 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10' => ! $isConfigured,
                ])>
                    <x-filament::icon icon="heroicon-o-link" class="h-6 w-6" />
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Conexión con Meta Cloud API</h2>
                        @if ($isVerified)
                            <x-filament::badge color="success" icon="heroicon-m-check-circle">
                                Verificada
                            </x-filament::badge>
                        @elseif ($isConfigured)
                            <x-filament::badge color="warning" icon="heroicon-m-arrow-path">
                                Pendiente de prueba
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="gray">
                                Sin configurar
                            </x-filament::badge>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        @if ($isVerified && $setting->verified_at)
                            Conexión confirmada {{ $setting->verified_at->diffForHumans() }}. La línea está lista para emitir y recibir mensajes.
                        @else
                            Configura las credenciales de tu número de WhatsApp Business Cloud API para operar el bot.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Parámetros del Webhook para Meta Developers --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-globe-alt" class="h-5 w-5 text-primary-500" />
                <span>Parámetros para el Webhook en Meta Developers</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Copia estos datos en tu panel de Meta for Developers (WhatsApp &gt; Configuración &gt; Webhook).
        </x-slot>

        <div class="grid grid-cols-1 gap-4 pt-1 sm:grid-cols-2">
            {{-- Webhook URL --}}
            <div x-data="{ copied: false }" class="rounded-xl bg-gray-50/75 p-3.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="mb-2 flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-gray-700 dark:text-gray-300">URL de Callback</span>
                    <button
                        type="button"
                        x-on:click="window.navigator.clipboard.writeText('{{ $this->getWebhookUrl() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center gap-1 font-semibold text-primary-600 transition-colors hover:text-primary-700 dark:text-primary-400"
                    >
                        <span x-text="copied ? '¡Copiado!' : 'Copiar'" class="text-[11px]"></span>
                        <x-filament::icon icon="heroicon-m-clipboard-document" class="h-3.5 w-3.5" />
                    </button>
                </div>
                <code class="block select-all rounded-lg bg-white px-3 py-2 font-mono text-xs text-gray-900 shadow-sm ring-1 ring-gray-950/5 break-all dark:bg-gray-900 dark:text-gray-100 dark:ring-white/10">
                    {{ $this->getWebhookUrl() }}
                </code>
            </div>

            {{-- Verify Token --}}
            <div x-data="{ copied: false }" class="rounded-xl bg-gray-50/75 p-3.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="mb-2 flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Token de verificación (hub.verify_token)</span>
                    <button
                        type="button"
                        x-on:click="window.navigator.clipboard.writeText('{{ $this->getWebhookVerifyToken() }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center gap-1 font-semibold text-primary-600 transition-colors hover:text-primary-700 dark:text-primary-400"
                    >
                        <span x-text="copied ? '¡Copiado!' : 'Copiar'" class="text-[11px]"></span>
                        <x-filament::icon icon="heroicon-m-clipboard-document" class="h-3.5 w-3.5" />
                    </button>
                </div>
                <code class="block select-all rounded-lg bg-white px-3 py-2 font-mono text-xs text-gray-900 shadow-sm ring-1 ring-gray-950/5 break-all dark:bg-gray-900 dark:text-gray-100 dark:ring-white/10">
                    {{ $this->getWebhookVerifyToken() ?: 'WHATSAPP_WEBHOOK_VERIFY_TOKEN (en .env)' }}
                </code>
            </div>
        </div>
    </x-filament::section>

    {{-- Formulario de Credenciales --}}
    <x-filament::section>
        <x-slot name="heading">
            Credenciales de la Cuenta
        </x-slot>

        <x-slot name="description">
            Token permanente generado con System User en Meta Business Manager y los IDs de la línea.
        </x-slot>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            {{-- Barra de Acciones y Footer --}}
            <div class="flex flex-col-reverse gap-3 pt-5 border-t border-gray-200 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    @unless ($lastTestSuccessful)
                        <p class="text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4 shrink-0" />
                            <span>Prueba la conexión exitosamente para habilitar el guardado.</span>
                        </p>
                    @endunless
                </div>

                <div class="flex items-center gap-3 justify-end">
                    {{ $this->testConnectionAction }}

                    <x-filament::button
                        type="submit"
                        icon="heroicon-m-check"
                        :disabled="! $lastTestSuccessful"
                    >
                        Guardar cambios
                    </x-filament::button>
                </div>
            </div>
        </form>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
