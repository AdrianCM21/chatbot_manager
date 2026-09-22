<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Services;

use App\Domains\Settings\Models\WhatsAppConnectionSetting;
use App\Domains\WhatsApp\Exceptions\WhatsAppApiException;
use Illuminate\Support\Facades\Http;

/**
 * Wrapper sobre la WhatsApp Cloud API (Meta) para mensajería y descarga de media.
 *
 * El token y el phone_number_id se cargan desde la configuración guardada en el
 * panel admin (Settings > Conexión con WhatsApp); las variables de entorno solo
 * sirven de fallback para entornos sin esa configuración cargada todavía.
 */
class WhatsAppClient
{
    private function accessToken(): string
    {
        return WhatsAppConnectionSetting::current()->access_token
            ?? (string) config('services.whatsapp.access_token');
    }

    private function phoneNumberId(): string
    {
        return WhatsAppConnectionSetting::current()->phone_number_id
            ?? (string) config('services.whatsapp.phone_number_id');
    }

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
        $accessToken = $this->accessToken();

        $meta = Http::withToken($accessToken)
            ->get("{$baseUrl}/{$mediaId}");

        if ($meta->failed()) {
            throw new WhatsAppApiException('No se pudo resolver la URL del media de WhatsApp.');
        }

        $binary = Http::withToken($accessToken)
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
        $baseUrl = rtrim((string) config('services.whatsapp.base_url'), '/');

        $response = Http::withToken($this->accessToken())
            ->post("{$baseUrl}/{$this->phoneNumberId()}/{$endpoint}", $payload);

        if ($response->failed()) {
            throw new WhatsAppApiException('Error al enviar mensaje por WhatsApp: '.$response->body());
        }
    }
}
