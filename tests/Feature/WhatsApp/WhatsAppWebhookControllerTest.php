<?php

declare(strict_types=1);

use App\Domains\Settings\Models\BotSetting;
use App\Domains\WhatsApp\Jobs\ProcessIncomingWhatsAppMessageJob;
use Illuminate\Support\Facades\Queue;

describe('verificación del webhook (GET)', function () {
    it('responde con el hub_challenge cuando el modo y el token son correctos', function () {
        config(['services.whatsapp.webhook_verify_token' => 'mi-token-secreto']);

        $response = $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'mi-token-secreto',
            'hub_challenge' => 'CHALLENGE_123',
        ]));

        $response->assertOk();
        $response->assertSeeText('CHALLENGE_123');
    });

    it('rechaza la verificación cuando el token no coincide', function () {
        config(['services.whatsapp.webhook_verify_token' => 'mi-token-secreto']);

        $response = $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'token-incorrecto',
            'hub_challenge' => 'CHALLENGE_123',
        ]));

        $response->assertForbidden();
    });

    it('rechaza la verificación cuando el modo no es subscribe', function () {
        config(['services.whatsapp.webhook_verify_token' => 'mi-token-secreto']);

        $response = $this->get('/webhooks/whatsapp?'.http_build_query([
            'hub_mode' => 'unsubscribe',
            'hub_verify_token' => 'mi-token-secreto',
            'hub_challenge' => 'CHALLENGE_123',
        ]));

        $response->assertForbidden();
    });
});

describe('recepción de mensajes (POST)', function () {
    beforeEach(function () {
        Queue::fake();
    });

    it('responde 200 y despacha el job de procesamiento para un mensaje de texto', function () {
        $payload = fixtureJson('whatsapp-text-message.json');

        $response = postWhatsAppWebhook($payload);

        $response->assertOk();
        Queue::assertPushed(ProcessIncomingWhatsAppMessageJob::class, fn (ProcessIncomingWhatsAppMessageJob $job) => $job->webhookPayload === $payload
        );
    });

    it('responde 200 y despacha el job de procesamiento para un mensaje de imagen', function () {
        $payload = fixtureJson('whatsapp-image-message.json');

        $response = postWhatsAppWebhook($payload);

        $response->assertOk();
        Queue::assertPushed(ProcessIncomingWhatsAppMessageJob::class);
    });

    it('rechaza el request si la firma X-Hub-Signature-256 es inválida', function () {
        $payload = fixtureJson('whatsapp-text-message.json');
        $body = json_encode($payload);

        $response = $this->call('POST', '/webhooks/whatsapp', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=firma-invalida',
        ], content: $body);

        $response->assertUnauthorized();
        Queue::assertNothingPushed();
    });

    it('rechaza el request si no se envía firma', function () {
        $payload = fixtureJson('whatsapp-text-message.json');

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertUnauthorized();
        Queue::assertNothingPushed();
    });

    it('no despacha el job si el bot está pausado, pero igual responde 200', function () {
        BotSetting::current()->update(['enabled' => false]);

        $payload = fixtureJson('whatsapp-text-message.json');

        $response = postWhatsAppWebhook($payload);

        $response->assertOk();
        Queue::assertNothingPushed();
    });

    it('despacha el job cuando el bot está activo', function () {
        BotSetting::current()->update(['enabled' => true]);

        $payload = fixtureJson('whatsapp-text-message.json');

        postWhatsAppWebhook($payload);

        Queue::assertPushed(ProcessIncomingWhatsAppMessageJob::class);
    });
});
