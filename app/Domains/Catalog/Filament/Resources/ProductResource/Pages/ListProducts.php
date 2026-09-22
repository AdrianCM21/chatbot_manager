<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources\ProductResource\Pages;

use App\Domains\Catalog\Filament\Resources\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
