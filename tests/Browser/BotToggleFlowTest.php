<?php

declare(strict_types=1);

use App\Domains\Settings\Models\BotSetting;
use App\Domains\WhatsApp\Models\IncomingMessage;
use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * Requiere: servidor real corriendo, Postgres+pgvector, y ChromeDriver
 * (php artisan dusk:chrome-driver). No se pudo ejecutar en el sandbox de
 * desarrollo por faltar ambas cosas — ver el resto de las limitaciones en
 * el README de tests.
 */
it('pausa el bot desde el panel y un mensaje de WhatsApp deja de generar respuesta', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/admin')
            ->waitForText('El bot está activo')
            ->assertSee('El bot está activo')
            ->press('Pausar bot')
            ->waitForText('¿Pausar el bot de WhatsApp?')
            ->press('Sí, pausar bot')
            ->waitForText('El bot está pausado')
            ->assertSee('El bot está pausado');
    });

    expect(BotSetting::isEnabled())->toBeFalse();

    // Con el bot pausado, el webhook no debe encolar ni procesar nada:
    // ni siquiera debería intentar llegar a DeepSeek o a WhatsApp.
    postWhatsAppWebhook(fixtureJson('whatsapp-text-message.json'));

    expect(IncomingMessage::count())->toBe(0);
});

it('reactiva el bot desde el panel', function () {
    $user = User::factory()->create();
    BotSetting::current()->update(['enabled' => false, 'paused_at' => now()]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/admin')
            ->waitForText('El bot está pausado')
            ->press('Activar bot')
            ->waitForText('El bot está activo')
            ->assertSee('El bot está activo');
    });

    expect(BotSetting::isEnabled())->toBeTrue();
});
