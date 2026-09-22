<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Actions;

use App\Domains\Catalog\Models\Product;
use App\Domains\WhatsApp\Services\WhatsAppClient;
use Illuminate\Database\Eloquent\Collection;

// TODO: Meta descarga la imagen desde $product->imageUrl(), que debe ser una URL
// pública (APP_URL accesible desde internet, o disco 's3'/CDN en producción).
class SendProductSuggestionsReplyAction
{
    public function __construct(
        private readonly WhatsAppClient $whatsApp,
    ) {}

    /**
     * @param  Collection<int, Product>  $products
     */
    public function execute(string $to, Collection $products): void
    {
        if ($products->isEmpty()) {
            $this->whatsApp->sendTextMessage(
                $to,
                'No encontramos productos que coincidan con tu búsqueda. ¿Podés darnos más detalles?'
            );

            return;
        }

        foreach ($products as $product) {
            $caption = sprintf('%s — Gs. %s (stock: %d)', $product->name, number_format((float) $product->price, 0, ',', '.'), $product->stock);

            if ($imageUrl = $product->imageUrl()) {
                $this->whatsApp->sendImageMessage($to, $imageUrl, $caption);
            } else {
                $this->whatsApp->sendTextMessage($to, $caption);
            }
        }
    }
}
