<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Services;

use OpenAI;
use OpenAI\Client;

/**
 * Wrapper sobre la API de DeepSeek (compatible con el formato OpenAI).
 */
class DeepSeekClient
{
    private Client $client;

    public function __construct()
    {
        $baseUrl = rtrim((string) config('services.deepseek.base_url'), '/');

        $this->client = OpenAI::factory()
            ->withApiKey((string) config('services.deepseek.api_key'))
            ->withBaseUri(str_ends_with($baseUrl, '/v1') ? $baseUrl : "{$baseUrl}/v1")
            ->make();
    }

    /**
     * Extrae del mensaje del cliente los términos relevantes de búsqueda
     * de catálogo (ej. "tenés zapatillas negras talle 42" -> "zapatillas negras talle 42").
     */
    public function interpretSearchQuery(string $message): string
    {
        $response = $this->client->chat()->create([
            'model' => config('services.deepseek.chat_model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Extraé del mensaje del cliente los términos de búsqueda de productos '
                        .'(nombre, categoría, características). Respondé solo con esos términos, sin explicaciones.',
                ],
                ['role' => 'user', 'content' => $message],
            ],
        ]);

        return trim($response->choices[0]->message->content ?? $message);
    }

    /**
     * Describe el producto visible en una imagen para poder buscarlo en el catálogo.
     */
    public function describeProductImage(string $imageBase64, string $mimeType = 'image/jpeg'): string
    {
        $response = $this->client->chat()->create([
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
        ]);

        return trim($response->choices[0]->message->content ?? '');
    }

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $response = $this->client->embeddings()->create([
            'model' => config('services.deepseek.embedding_model'),
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding ?? [];
    }
}
