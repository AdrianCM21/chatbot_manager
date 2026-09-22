<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Services\DeepSeekClient;
use Illuminate\Database\Eloquent\Collection;

class IdentifyProductFromImageAction
{
    public function __construct(
        private readonly DeepSeekClient $deepSeek,
        private readonly SearchProductsBySemanticQueryAction $searchProducts,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function execute(string $imageBinary, string $mimeType = 'image/jpeg', int $limit = 5): Collection
    {
        $description = $this->deepSeek->describeProductImage(base64_encode($imageBinary), $mimeType);

        return $this->searchProducts->execute($description, $limit);
    }
}
