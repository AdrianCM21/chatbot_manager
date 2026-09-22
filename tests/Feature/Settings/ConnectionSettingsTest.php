<?php

declare(strict_types=1);

use App\Domains\Settings\Filament\Pages\ConnectionSettings;
use App\Domains\Settings\Models\WhatsAppConnectionSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('no guarda si nunca se probó la conexión', function () {
    Livewire::test(ConnectionSettings::class)
        ->fillForm([
            'access_token' => 'EAAG_NUEVO_TOKEN',
            'phone_number_id' => '111222333',
            'business_account_id' => '444555666',
        ])
        ->call('save');

    expect(WhatsAppConnectionSetting::current()->access_token)->toBeNull();
});

it('rechaza guardar cuando la última prueba de conexión falló', function () {
    Http::fake([
        '*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401),
    ]);

    Livewire::test(ConnectionSettings::class)
        ->fillForm([
            'access_token' => 'EAAG_TOKEN_INVALIDO',
            'phone_number_id' => '111222333',
            'business_account_id' => '444555666',
        ])
        ->mountAction('testConnection')
        ->callMountedAction()
        ->call('save');

    expect(WhatsAppConnectionSetting::current()->access_token)->toBeNull();
});

it('guarda la configuración cuando la prueba de conexión fue exitosa', function () {
    Http::fake([
        '*' => Http::response(['verified_name' => 'Mi Tienda']),
    ]);

    Livewire::test(ConnectionSettings::class)
        ->fillForm([
            'access_token' => 'EAAG_TOKEN_VALIDO',
            'phone_number_id' => '111222333',
            'business_account_id' => '444555666',
        ])
        ->mountAction('testConnection')
        ->callMountedAction()
        ->call('save');

    $setting = WhatsAppConnectionSetting::current();

    expect($setting->access_token)->toBe('EAAG_TOKEN_VALIDO')
        ->and($setting->phone_number_id)->toBe('111222333')
        ->and($setting->business_account_id)->toBe('444555666')
        ->and($setting->verified_at)->not->toBeNull();
});

it('vuelve a exigir probar la conexión si se cambia el token después de un test exitoso', function () {
    Http::fake(['*' => Http::response(['verified_name' => 'Mi Tienda'])]);

    Livewire::test(ConnectionSettings::class)
        ->fillForm(['access_token' => 'EAAG_PRIMER_TOKEN', 'phone_number_id' => '111', 'business_account_id' => '222'])
        ->mountAction('testConnection')
        ->callMountedAction()
        ->fillForm(['access_token' => 'EAAG_OTRO_TOKEN'])
        ->call('save');

    expect(WhatsAppConnectionSetting::current()->access_token)->toBeNull();
});

it('no pisa el token guardado cuando se deja el campo vacío al editar otros datos', function () {
    Http::fake(['*' => Http::response(['verified_name' => 'Mi Tienda'])]);

    WhatsAppConnectionSetting::current()->update([
        'access_token' => 'EAAG_TOKEN_YA_GUARDADO',
        'phone_number_id' => '111',
        'business_account_id' => '222',
    ]);

    Livewire::test(ConnectionSettings::class)
        ->fillForm([
            'access_token' => null,
            'phone_number_id' => '111',
            'business_account_id' => '999999',
        ])
        ->mountAction('testConnection')
        ->callMountedAction()
        ->call('save');

    $setting = WhatsAppConnectionSetting::current();

    expect($setting->access_token)->toBe('EAAG_TOKEN_YA_GUARDADO')
        ->and($setting->business_account_id)->toBe('999999');
});
