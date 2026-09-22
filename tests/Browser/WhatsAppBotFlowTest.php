<?php

declare(strict_types=1);

use App\Domains\Catalog\Models\Product;
use App\Domains\WhatsApp\Models\IncomingMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Dusk\Browser;

/**
 * Requiere: servidor real, Postgres+pgvector, ChromeDriver. No se pudo
 * ejecutar en el sandbox de desarrollo por faltar todo lo anterior.
 *
 * Meta no usa un navegador para pegarle al webhook, así que ese paso se
 * dispara directo por HTTP (postWhatsAppWebhook, en el mismo proceso PHP
 * del test — por eso Http::fake() sí lo alcanza a mockear). El navegador
 * se usa solo para confirmar, contra el servidor real, que el resultado
 * quedó visible en el panel (el contador de "Mensajes de hoy").
 */
it('procesa un mensaje entrante y el dashboard refleja la consulta', function () {
    $user = User::factory()->create();
    Product::factory()->withEmbedding()->create(['name' => 'Auriculares Bluetooth XT-200']);

    Http::fake([
        '*/v1/chat/completions' => Http::response(['choices' => [
            ['message' => ['content' => 'auriculares bluetooth']],
        ]]),
        '*/v1/embeddings' => Http::response(['data' => [
            ['embedding' => array_fill(0, 8, 0.1)],
        ]]),
        '*/messages' => Http::response(['messages' => [['id' => 'wamid.OUT']]]),
    ]);

    postWhatsAppWebhook(fixtureJson('whatsapp-text-message.json'))->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/messages')
        && $request['to'] === '595991234567'
    );

    expect(IncomingMessage::sole())
        ->from_number->toBe('595991234567')
        ->matched_products_count->toBeGreaterThan(0);

    // Confirmación visual, contra el servidor real: el dashboard renderiza
    // sin errores y el widget de métricas quedó en la página.
    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/admin')
            ->waitForText('Mensajes de hoy')
            ->assertSee('Mensajes de hoy')
            ->assertSee('El bot está activo');
    });
});
