<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources\ProductResource\Pages;

use App\Domains\Catalog\Filament\Resources\ProductResource;
use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        GenerateProductEmbeddingJob::dispatch($this->record);
    }
}
