<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Jobs;

use App\Domains\Catalog\Actions\IdentifyProductFromImageAction;
use App\Domains\Catalog\Actions\SearchProductsBySemanticQueryAction;
use App\Domains\WhatsApp\Actions\SendProductSuggestionsReplyAction;
use App\Domains\WhatsApp\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $webhookPayload  Payload crudo del webhook de Meta.
     */
    public function __construct(
        public readonly array $webhookPayload,
    ) {}

    public function handle(
        SearchProductsBySemanticQueryAction $searchProducts,
        IdentifyProductFromImageAction $identifyProduct,
        SendProductSuggestionsReplyAction $sendReply,
        WhatsAppClient $whatsApp,
    ): void {
        foreach ($this->extractMessages() as $message) {
            $from = $message['from'] ?? null;

            if (! $from) {
                continue;
            }

            try {
                $products = match ($message['type'] ?? null) {
                    'text' => $searchProducts->execute($message['text']['body'] ?? ''),
                    'image' => $this->handleImageMessage($message, $whatsApp, $identifyProduct),
                    default => null,
                };

                if ($products === null) {
                    continue;
                }

                $sendReply->execute($from, $products);
            } catch (\Throwable $e) {
                Log::error('Error procesando mensaje entrante de WhatsApp', [
                    'from' => $from,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    private function handleImageMessage(array $message, WhatsAppClient $whatsApp, IdentifyProductFromImageAction $identifyProduct): ?Collection
    {
        $mediaId = $message['image']['id'] ?? null;

        if (! $mediaId) {
            return null;
        }

        $media = $whatsApp->downloadMedia($mediaId);

        return $identifyProduct->execute($media['binary'], $media['mime_type']);
    }

    /**
     * Aplana el payload del webhook de Meta a una lista simple de mensajes entrantes.
     *
     * @return array<int, array{from: string, type: string, text?: array, image?: array}>
     */
    private function extractMessages(): array
    {
        $messages = [];

        foreach ($this->webhookPayload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $message) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }
}
