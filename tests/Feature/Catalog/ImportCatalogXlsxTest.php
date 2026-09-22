<?php

declare(strict_types=1);

use App\Domains\Catalog\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
use App\Domains\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;

/**
 * Ejercita el importador nativo de Filament de punta a punta (modal, mapeo
 * de columnas, cola de procesamiento) con el fixture real de tests/Fixtures.
 *
 * Requiere ext-zip (lectura del .xlsx) y la base de test en Postgres; no se
 * pudo ejecutar en el sandbox de desarrollo por esas dos razones — ver
 * tests/Unit/Catalog/ImportProductsActionXlsxConversionTest.php para el
 * detalle de la limitación de ext-zip.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Redis::shouldReceive('incr')->byDefault();
    Redis::shouldReceive('get')->andReturn(0)->byDefault();
    Redis::shouldReceive('del')->byDefault();
});

it('sube un xlsx, crea y actualiza productos según el mapeo de columnas, y encola la regeneración de embeddings', function () {
    // Solo fakeamos el job de embeddings: el resto (incluido el procesamiento
    // real del import, que corre sync) sigue su curso normal para poder
    // verificar qué quedó en la base.
    Queue::fake([GenerateProductEmbeddingJob::class]);

    $existente = Product::factory()->create(['name' => 'Zapatillas Urbanas Runner', 'stock' => 1]);

    $file = fakeTemporaryUploadedFile('catalog-sample.xlsx', 'catalog-sample.xlsx');

    Livewire::test(ListProducts::class)
        ->mountTableAction('import')
        ->setTableActionData([
            'file' => $file,
            'columnMap' => [
                'name' => 'Producto',
                'category' => 'Rubro',
                'price' => 'Valor',
                'stock' => 'Existencia',
                'description' => 'Detalle',
            ],
        ])
        ->callMountedTableAction();

    // Las dos filas válidas del fixture: una crea un producto nuevo, la otra
    // actualiza el que ya existía (dedupe por nombre, sin importar mayúsculas).
    $nuevo = Product::where('name', 'Auriculares Bluetooth XT-200')->first();
    expect($nuevo)->not->toBeNull()
        ->and($nuevo->category)->toBe('Audio')
        ->and((float) $nuevo->price)->toBe(185000.0);

    $existente->refresh();
    expect($existente->stock)->toBe(8)
        ->and((float) $existente->price)->toBe(320000.0);

    // Las dos filas inválidas (sin nombre / precio no numérico) no crean productos.
    expect(Product::count())->toBe(2);

    Queue::assertPushed(GenerateProductEmbeddingJob::class, 2);
});
