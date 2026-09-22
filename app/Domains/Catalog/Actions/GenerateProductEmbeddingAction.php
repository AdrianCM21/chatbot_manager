<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Services\DeepSeekClient;

class GenerateProductEmbeddingAction
{
    public function __construct(
        private readonly DeepSeekClient $deepSeek,
    ) {}

    public function execute(Product $product): Product
    {
        $embedding = $this->deepSeek->embed($product->embeddingSourceText());

        $product->update(['embedding' => $embedding]);

        return $product;
    }
}
