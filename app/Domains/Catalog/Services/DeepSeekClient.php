<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Wrapper sobre la API de DeepSeek (compatible con el formato OpenAI) usando
 * el cliente Http de Laravel, para que Http::fake() pueda mockearla en tests.
 */
class DeepSeekClient
{
    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.deepseek.base_url'), '/');
        $baseUrl = str_ends_with($baseUrl, '/v1') ? $baseUrl : "{$baseUrl}/v1";

        return Http::baseUrl($baseUrl)
            ->withToken((string) config('services.deepseek.api_key'))
            ->acceptJson();
    }

    /**
     * Extrae del mensaje del cliente los términos relevantes de búsqueda
     * de catálogo (ej. "tenés zapatillas negras talle 42" -> "zapatillas negras talle 42").
     */
    public function interpretSearchQuery(string $message): string
    {
        $response = $this->client()->post('/chat/completions', [
            'model' => config('services.deepseek.chat_model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Extraé del mensaje del cliente los términos de búsqueda de productos '
                        .'(nombre, categoría, características). Respondé solo con esos términos, sin explicaciones.',
                ],
                ['role' => 'user', 'content' => $message],
            ],
        ])->throw();

        return trim($response->json('choices.0.message.content') ?? $message);
    }

    /**
     * Describe el producto visible en una imagen para poder buscarlo en el catálogo.
     */
    public function describeProductImage(string $imageBase64, string $mimeType = 'image/jpeg'): string
    {
        $response = $this->client()->post('/chat/completions', [
            'model' => config('services.deepseek.vision_model'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Describí brevemente el producto de esta imagen (tipo, color, '
                                .'características) para buscarlo en un catálogo de tienda.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => "data:{$mimeType};base64,{$imageBase64}"],
                        ],
                    ],
                ],
            ],
        ])->throw();

        return trim($response->json('choices.0.message.content') ?? '');
    }

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $response = $this->client()->post('/embeddings', [
            'model' => config('services.deepseek.embedding_model'),
            'input' => $text,
        ])->throw();

        return $response->json('data.0.embedding') ?? [];
    }
}
