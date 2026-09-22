<?php

declare(strict_types=1);

namespace App\Domains\Settings\Filament\Widgets;

use App\Domains\Settings\Actions\ToggleBotStatusAction;
use App\Domains\Settings\Models\BotSetting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class BotStatusWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.bot-status-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    public function getBotSetting(): BotSetting
    {
        return BotSetting::current();
    }

    public function toggleBotAction(): Action
    {
        $isEnabled = $this->getBotSetting()->enabled;

        return Action::make('toggleBot')
            ->label($isEnabled ? 'Pausar bot' : 'Activar bot')
            ->color($isEnabled ? 'danger' : 'success')
            ->icon($isEnabled ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
            ->size('lg')
            ->requiresConfirmation($isEnabled)
            ->modalHeading('¿Pausar el bot de WhatsApp?')
            ->modalDescription('Mientras esté pausado, tus clientes van a dejar de recibir respuestas automáticas por WhatsApp. Vas a poder reactivarlo cuando quieras.')
            ->modalSubmitActionLabel('Sí, pausar bot')
            ->action(function () use ($isEnabled): void {
                app(ToggleBotStatusAction::class)->execute(! $isEnabled, auth()->user());

                Notification::make()
                    ->title($isEnabled ? 'Bot pausado' : 'Bot activado')
                    ->body($isEnabled
                        ? 'El bot dejó de responder mensajes de WhatsApp hasta que lo vuelvas a activar.'
                        : 'El bot volvió a responder mensajes de WhatsApp automáticamente.')
                    ->success()
                    ->send();
            });
    }
}
