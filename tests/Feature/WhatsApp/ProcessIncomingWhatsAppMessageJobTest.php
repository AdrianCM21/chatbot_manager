<?php

declare(strict_types=1);

use App\Domains\Catalog\Models\Product;
use App\Domains\WhatsApp\Jobs\ProcessIncomingWhatsAppMessageJob;
use App\Domains\WhatsApp\Models\IncomingMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.deepseek.base_url' => 'https://api.deepseek.com',
        'services.whatsapp.base_url' => 'https://graph.facebook.com/v21.0',
        'services.whatsapp.access_token' => 'test-token',
        'services.whatsapp.phone_number_id' => 'test-phone-number-id',
    ]);
});

it('interpreta el mensaje de texto, busca productos en el catálogo y responde por WhatsApp', function () {
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

    ProcessIncomingWhatsAppMessageJob::dispatchSync(fixtureJson('whatsapp-text-message.json'));

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v1/chat/completions'));
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/v1/embeddings'));
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/messages')
        && $request['to'] === '595991234567'
    );

    $message = IncomingMessage::sole();

    expect($message->from_number)->toBe('595991234567')
        ->and($message->type)->toBe('text')
        ->and($message->matched_products_count)->toBeGreaterThan(0);
});

it('descarga el media, identifica el producto de la foto y responde por WhatsApp', function () {
    Product::factory()->withEmbedding()->create();

    Http::fake([
        '*/MEDIA_ID_123' => Http::response(['url' => 'https://fake-media-cdn.test/photo.jpg', 'mime_type' => 'image/jpeg']),
        'fake-media-cdn.test/*' => Http::response('contenido-binario-de-la-foto'),
        '*/v1/chat/completions' => Http::response(['choices' => [
            ['message' => ['content' => 'auriculares negros']],
        ]]),
        '*/v1/embeddings' => Http::response(['data' => [
            ['embedding' => array_fill(0, 8, 0.2)],
        ]]),
        '*/messages' => Http::response(['messages' => [['id' => 'wamid.OUT']]]),
    ]);

    ProcessIncomingWhatsAppMessageJob::dispatchSync(fixtureJson('whatsapp-image-message.json'));

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'MEDIA_ID_123'));
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'fake-media-cdn.test'));
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/messages'));

    $message = IncomingMessage::sole();

    expect($message->from_number)->toBe('595991234567')
        ->and($message->type)->toBe('image');
});

it('avisa que no encontró productos cuando el catálogo está vacío', function () {
    Http::fake([
        '*/v1/chat/completions' => Http::response(['choices' => [
            ['message' => ['content' => 'algo que no está en el catálogo']],
        ]]),
        '*/v1/embeddings' => Http::response(['data' => [
            ['embedding' => array_fill(0, 8, 0.1)],
        ]]),
        '*/messages' => Http::response(['messages' => [['id' => 'wamid.OUT']]]),
    ]);

    ProcessIncomingWhatsAppMessageJob::dispatchSync(fixtureJson('whatsapp-text-message.json'));

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/messages')
        && str_contains($request['text']['body'] ?? '', 'No encontramos productos')
    );

    expect(IncomingMessage::sole()->matched_products_count)->toBe(0);
});

it('no rompe el job ni escribe el mensaje si DeepSeek falla', function () {
    Http::fake([
        '*/v1/chat/completions' => Http::response(['error' => 'DeepSeek no disponible'], 500),
    ]);

    ProcessIncomingWhatsAppMessageJob::dispatchSync(fixtureJson('whatsapp-text-message.json'));

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/messages'));
    expect(IncomingMessage::count())->toBe(0);
});

it('ignora tipos de mensaje que no sabe procesar (ej. video, sticker)', function () {
    Http::fake();

    $payload = fixtureJson('whatsapp-text-message.json');
    $payload['entry'][0]['changes'][0]['value']['messages'][0]['type'] = 'sticker';
    unset($payload['entry'][0]['changes'][0]['value']['messages'][0]['text']);

    ProcessIncomingWhatsAppMessageJob::dispatchSync($payload);

    Http::assertNothingSent();
    expect(IncomingMessage::count())->toBe(0);
});
