<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Services\DeepSeekClient;
use Illuminate\Database\Eloquent\Collection;
use Pgvector\Laravel\Distance;

class SearchProductsBySemanticQueryAction
{
    public function __construct(
        private readonly DeepSeekClient $deepSeek,
    ) {}

    /**
     * @return Collection<int, Product>
     */
    public function execute(string $customerMessage, int $limit = 5): Collection
    {
        $searchTerms = $this->deepSeek->interpretSearchQuery($customerMessage);
        $embedding = $this->deepSeek->embed($searchTerms);

        return Product::query()
            ->nearestNeighbors('embedding', $embedding, Distance::Cosine)
            ->limit($limit)
            ->get();
    }
}
