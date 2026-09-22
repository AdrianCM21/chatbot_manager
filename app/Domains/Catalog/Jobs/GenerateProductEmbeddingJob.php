<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Jobs;

use App\Domains\Catalog\Actions\GenerateProductEmbeddingAction;
use App\Domains\Catalog\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateProductEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Product $product,
    ) {}

    public function handle(GenerateProductEmbeddingAction $action): void
    {
        $action->execute($this->product);
    }
}
