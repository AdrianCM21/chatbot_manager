<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources\ProductResource\Pages;

use App\Domains\Catalog\Actions\GenerateProductEmbeddingAction;
use App\Domains\Catalog\Filament\Resources\ProductResource;
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
        app(GenerateProductEmbeddingAction::class)->execute($this->record);
    }
}
