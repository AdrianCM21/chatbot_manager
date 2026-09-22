<?php

declare(strict_types=1);

namespace App\Domains\Settings\Actions;

use Illuminate\Support\Facades\Http;

class TestWhatsAppConnectionAction
{
    /**
     * @return array{success: bool, message: string}
     */
    public function execute(string $accessToken, string $phoneNumberId): array
    {
        if (blank($accessToken) || blank($phoneNumberId)) {
            return [
                'success' => false,
                'message' => 'Completá el token y el ID de número de teléfono antes de probar la conexión.',
            ];
        }

        $baseUrl = rtrim((string) config('services.whatsapp.base_url'), '/');

        $response = Http::withToken($accessToken)
            ->get("{$baseUrl}/{$phoneNumberId}", ['fields' => 'verified_name,display_phone_number']);

        if ($response->successful()) {
            $name = $response->json('verified_name') ?? $response->json('display_phone_number') ?? 'tu número';

            return [
                'success' => true,
                'message' => "Conexión exitosa. WhatsApp reconoce este número como \"{$name}\".",
            ];
        }

        $errorMessage = $response->json('error.message');

        return [
            'success' => false,
            'message' => $errorMessage
                ? "WhatsApp rechazó la conexión: {$errorMessage}"
                : 'No se pudo conectar con WhatsApp. Verificá el token y el ID de número de teléfono.',
        ];
    }
}
