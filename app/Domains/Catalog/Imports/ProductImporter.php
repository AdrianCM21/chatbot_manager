<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Imports;

use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
use App\Domains\Catalog\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nombre')
                ->guess(['nombre', 'producto', 'product', 'name', 'articulo', 'artículo'])
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('category')
                ->label('Categoría')
                ->guess(['categoria', 'categoría', 'category', 'rubro'])
                ->rules(['nullable', 'max:255']),

            // Ojo: no usamos ->numeric()/->integer() acá. Esos helpers "limpian" el
            // valor con una regex antes de validar (preg_replace('/[^0-9.-]/', ...))
            // y un texto sin ningún dígito (ej. "no-es-un-numero") termina en 0 en
            // vez de fallar la validación. Validamos el string crudo con la regla
            // 'numeric'/'integer' y dejamos que el cast decimal:2 / integer del
            // modelo Product haga la conversión una vez que ya pasó la validación.
            ImportColumn::make('price')
                ->label('Precio')
                ->guess(['precio', 'price', 'valor'])
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('stock')
                ->label('Stock')
                ->guess(['stock', 'existencia', 'cantidad', 'inventario'])
                ->rules(['nullable', 'integer', 'min:0']),

            ImportColumn::make('description')
                ->label('Descripción')
                ->guess(['descripcion', 'descripción', 'description', 'detalle'])
                ->rules(['nullable']),
        ];
    }

    /**
     * El catálogo no trae un ID: dedupe por nombre para decidir si se crea o
     * se actualiza un producto existente.
     */
    public function resolveRecord(): ?Model
    {
        $name = trim((string) ($this->data['name'] ?? ''));

        if ($name === '') {
            return new Product;
        }

        return Product::query()
            ->whereRaw('lower(name) = ?', [Str::lower($name)])
            ->first() ?? new Product;
    }

    public function afterCreate(): void
    {
        // INCR directo (no vía Cache::increment) porque varios chunks del mismo
        // import corren en paralelo en distintos workers y necesitan un contador
        // realmente atómico, no el "select + update" del driver de cache por defecto.
        Redis::incr($this->createdCounterKey());
        GenerateProductEmbeddingJob::dispatch($this->record);
    }

    public function afterUpdate(): void
    {
        Redis::incr($this->updatedCounterKey());
        GenerateProductEmbeddingJob::dispatch($this->record);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $created = (int) Redis::get(self::counterKey($import, 'created'));
        $updated = (int) Redis::get(self::counterKey($import, 'updated'));
        $failed = $import->getFailedRowsCount();

        $body = "Se procesaron {$import->total_rows} filas del catálogo: {$created} productos nuevos, {$updated} actualizados.";

        if ($failed > 0) {
            $body .= " {$failed} filas no se pudieron importar.";
        }

        Redis::del(self::counterKey($import, 'created'), self::counterKey($import, 'updated'));

        return $body;
    }

    private function createdCounterKey(): string
    {
        return self::counterKey($this->import, 'created');
    }

    private function updatedCounterKey(): string
    {
        return self::counterKey($this->import, 'updated');
    }

    private static function counterKey(Import $import, string $suffix): string
    {
        return "product-import:{$import->getKey()}:{$suffix}";
    }
}
