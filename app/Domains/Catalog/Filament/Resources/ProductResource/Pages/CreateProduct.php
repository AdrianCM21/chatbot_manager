<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources\ProductResource\Pages;

use App\Domains\Catalog\Actions\GenerateProductEmbeddingAction;
use App\Domains\Catalog\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        app(GenerateProductEmbeddingAction::class)->execute($this->record);
    }
}
