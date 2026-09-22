<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Datos de conexión
        </x-slot>

        <x-slot name="description">
            Cargá acá las credenciales de tu cuenta de WhatsApp Business para que el bot pueda enviar y recibir mensajes.
        </x-slot>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                @unless ($lastTestSuccessful)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Probá la conexión antes de guardar.
                    </p>
                @endunless

                {{ $this->testConnectionAction }}

                <x-filament::button type="submit" icon="heroicon-o-check">
                    Guardar cambios
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
