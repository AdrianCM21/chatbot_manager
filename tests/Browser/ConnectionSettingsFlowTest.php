<?php

declare(strict_types=1);

use App\Domains\Settings\Models\WhatsAppConnectionSetting;
use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * Requiere: servidor real, Postgres+pgvector, ChromeDriver, y acceso de red
 * real a graph.facebook.com (el botón "Probar conexión" llama a la Graph
 * API de verdad; con un token inventado, Meta responde con un error real
 * de OAuth). No se pudo ejecutar en el sandbox de desarrollo.
 */
it('muestra el error de Meta con un token inválido y no guarda los datos', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/admin/connection-settings')
            ->waitForText('Conexión con WhatsApp')
            ->type('data.access_token', 'token-invalido-de-prueba-123')
            ->type('data.phone_number_id', '000000000000000')
            ->type('data.business_account_id', '111111111111111')
            ->press('Probar conexión')
            ->waitForText('No se pudo conectar', 15)
            ->assertSee('No se pudo conectar')
            ->assertDontSee('Configuración guardada')
            ->press('Guardar cambios')
            ->waitForText('No se pudo guardar')
            ->assertSee('Probá la conexión antes de guardar');
    });

    expect(WhatsAppConnectionSetting::current()->access_token)->toBeNull();
});
