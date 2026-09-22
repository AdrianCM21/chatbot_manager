<?php

declare(strict_types=1);

namespace App\Domains\Settings\Filament\Pages;

use App\Domains\Settings\Actions\SaveWhatsAppConnectionSettingsAction;
use App\Domains\Settings\Actions\TestWhatsAppConnectionAction;
use App\Domains\Settings\Models\WhatsAppConnectionSetting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ConnectionSettings extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Conexión con WhatsApp';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $title = 'Conexión con WhatsApp';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.connection-settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public bool $lastTestSuccessful = false;

    public function mount(): void
    {
        $setting = $this->getSetting();

        $this->form->fill([
            'access_token' => null,
            'phone_number_id' => $setting->phone_number_id,
            'business_account_id' => $setting->business_account_id,
        ]);

        $this->lastTestSuccessful = false;
    }

    public function getSetting(): WhatsAppConnectionSetting
    {
        return WhatsAppConnectionSetting::current();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('access_token')
                    ->label('Token de acceso de WhatsApp')
                    ->password()
                    ->revealable()
                    ->placeholder(fn (): string => $this->getSetting()->isConfigured()
                        ? 'Token guardado terminado en '.$this->getSetting()->maskedAccessToken().' — dejalo vacío para mantenerlo'
                        : 'Pegá acá el token de acceso permanente del System User')
                    ->helperText('Por seguridad, nunca se muestra el token completo una vez guardado.')
                    ->afterStateUpdated(fn () => $this->lastTestSuccessful = false)
                    ->live(onBlur: true)
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                TextInput::make('phone_number_id')
                    ->label('ID de número de teléfono')
                    ->required()
                    ->maxLength(255)
                    ->afterStateUpdated(fn () => $this->lastTestSuccessful = false)
                    ->live(onBlur: true),

                TextInput::make('business_account_id')
                    ->label('ID de cuenta de WhatsApp Business')
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function testConnectionAction(): Action
    {
        return Action::make('testConnection')
            ->label('Probar conexión')
            ->color('gray')
            ->icon('heroicon-o-signal')
            ->action(function (): void {
                // Raw state (no valida el formulario completo): probar conexión solo
                // necesita token + phone_number_id, y no debe bloquearse si todavía
                // falta cargar el business_account_id.
                $state = $this->form->getRawState();

                $token = filled($state['access_token'] ?? null)
                    ? $state['access_token']
                    : $this->getSetting()->access_token;

                $result = app(TestWhatsAppConnectionAction::class)->execute(
                    (string) $token,
                    (string) ($state['phone_number_id'] ?? ''),
                );

                $this->lastTestSuccessful = $result['success'];

                Notification::make()
                    ->title($result['success'] ? 'Conexión exitosa' : 'No se pudo conectar')
                    ->body($result['message'])
                    ->color($result['success'] ? 'success' : 'danger')
                    ->send();
            });
    }

    public function save(): void
    {
        // No guardamos credenciales sin confirmar que funcionan: hay que probar
        // la conexión (con éxito) antes de cada guardado.
        if (! $this->lastTestSuccessful) {
            Notification::make()
                ->title('No se pudo guardar')
                ->body('Probá la conexión antes de guardar los cambios.')
                ->danger()
                ->send();

            return;
        }

        $state = $this->form->getState();

        app(SaveWhatsAppConnectionSettingsAction::class)->execute($state, true);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();

        $this->mount();
    }
}
