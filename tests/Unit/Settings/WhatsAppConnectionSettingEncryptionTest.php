<?php

declare(strict_types=1);

use App\Domains\Settings\Models\WhatsAppConnectionSetting;
use Illuminate\Support\Facades\DB;

it('nunca guarda el token de acceso en texto plano en la base de datos', function () {
    $setting = WhatsAppConnectionSetting::current();
    $setting->update(['access_token' => 'EAAG_TOKEN_SUPER_SECRETO_123']);

    $rawValue = DB::table('whatsapp_connection_settings')
        ->where('id', $setting->id)
        ->value('access_token');

    expect($rawValue)->not->toBeNull()
        ->and($rawValue)->not->toBe('EAAG_TOKEN_SUPER_SECRETO_123')
        ->and($rawValue)->not->toContain('EAAG_TOKEN_SUPER_SECRETO_123');
});

it('desencripta el token correctamente al leerlo a través del modelo', function () {
    $setting = WhatsAppConnectionSetting::current();
    $setting->update(['access_token' => 'EAAG_TOKEN_SUPER_SECRETO_123']);

    $fresh = WhatsAppConnectionSetting::query()->find($setting->id);

    expect($fresh->access_token)->toBe('EAAG_TOKEN_SUPER_SECRETO_123');
});

it('el atributo access_token queda oculto al serializar el modelo (toArray/toJson)', function () {
    $setting = WhatsAppConnectionSetting::current();
    $setting->update(['access_token' => 'EAAG_TOKEN_SUPER_SECRETO_123']);

    $array = $setting->fresh()->toArray();

    expect($array)->not->toHaveKey('access_token');
});

it('maskedAccessToken solo expone los últimos 4 caracteres', function () {
    $setting = WhatsAppConnectionSetting::current();
    $setting->update(['access_token' => 'EAAG_TOKEN_SUPER_SECRETO_1234']);

    expect($setting->maskedAccessToken())->toBe('•••• •••• 1234')
        ->and($setting->maskedAccessToken())->not->toContain('SECRETO');
});

it('maskedAccessToken devuelve null cuando todavía no hay token guardado', function () {
    $setting = WhatsAppConnectionSetting::current();

    expect($setting->access_token)->toBeNull()
        ->and($setting->maskedAccessToken())->toBeNull()
        ->and($setting->isConfigured())->toBeFalse();
});
