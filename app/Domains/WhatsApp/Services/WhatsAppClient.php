<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Services;

use App\Domains\WhatsApp\Exceptions\WhatsAppApiException;
use Illuminate\Support\Facades\Http;

/**
 * Wrapper sobre la WhatsApp Cloud API (Meta) para mensajería y descarga de media.
 */
class WhatsAppClient
{
    public function sendTextMessage(string $to, string $body): void
    {
        $this->post('messages', [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ]);
    }

    public function sendImageMessage(string $to, string $imageUrl, ?string $caption = null): void
    {
        $this->post('messages', [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => array_filter([
                'link' => $imageUrl,
                'caption' => $caption,
            ]),
        ]);
    }

    /**
     * Descarga el binario de un media entrante (foto enviada por el cliente).
     *
     * @return array{binary: string, mime_type: string}
     */
    public function downloadMedia(string $mediaId): array
    {
        $baseUrl = rtrim((string) config('services.whatsapp.base_url'), '/');

        $meta = Http::withToken((string) config('services.whatsapp.access_token'))
            ->get("{$baseUrl}/{$mediaId}");

        if ($meta->failed()) {
            throw new WhatsAppApiException('No se pudo resolver la URL del media de WhatsApp.');
        }

        $binary = Http::withToken((string) config('services.whatsapp.access_token'))
            ->get($meta->json('url'));

        if ($binary->failed()) {
            throw new WhatsAppApiException('No se pudo descargar el media de WhatsApp.');
        }

        return [
            'binary' => $binary->body(),
            'mime_type' => $meta->json('mime_type', 'image/jpeg'),
        ];
    }

    private function post(string $endpoint, array $payload): void
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $baseUrl = rtrim((string) config('services.whatsapp.base_url'), '/');

        $response = Http::withToken((string) config('services.whatsapp.access_token'))
            ->post("{$baseUrl}/{$phoneNumberId}/{$endpoint}", $payload);

        if ($response->failed()) {
            throw new WhatsAppApiException('Error al enviar mensaje por WhatsApp: '.$response->body());
        }
    }
}
