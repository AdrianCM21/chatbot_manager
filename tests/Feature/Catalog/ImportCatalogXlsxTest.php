<?php

declare(strict_types=1);

use App\Domains\Catalog\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
use App\Domains\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;

/**
 * Ejercita el importador nativo de Filament de punta a punta (modal, mapeo
 * de columnas, cola de procesamiento) con el fixture real de tests/Fixtures.
 *
 * Requiere ext-zip (lectura del .xlsx) y las migraciones de Filament Actions
 * publicadas (`php artisan vendor:publish --tag=filament-actions-migrations`).
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Redis::shouldReceive('incr')->byDefault();
    Redis::shouldReceive('get')->andReturn(0)->byDefault();
    Redis::shouldReceive('del')->byDefault();

    // Filament guarda getRealPath() del archivo subido en imports.file_path
    // (varchar 255). El simulador de upload de Livewire codifica hash +
    // mimeType + size en el nombre físico del archivo, y el mimetype de un
    // .xlsx (application/vnd.openxmlformats-officedocument.spreadsheetml.sheet)
    // es tan largo que el path se pasa de 255 salvo que la raíz de storage
    // sea bien corta — no es un problema real de producción (storage_path()
    // ahí suele ser mucho más corto que este proyecto), así que acá se
    // acorta solo para el test.
    $this->app->useStoragePath('/tmp/t'.substr(uniqid(), -6));
});

it('sube un xlsx, crea y actualiza productos según el mapeo de columnas, y encola la regeneración de embeddings', function () {
    // Solo fakeamos el job de embeddings: el resto (incluido el procesamiento
    // real del import, que corre sync) sigue su curso normal para poder
    // verificar qué quedó en la base.
    Queue::fake([GenerateProductEmbeddingJob::class]);

    $existente = Product::factory()->create(['name' => 'Zapatillas Urbanas Runner', 'stock' => 1]);

    // Nombre corto a propósito: el simulador de Livewire ya codifica hash +
    // mimeType + size en el nombre físico (ver comentario en el beforeEach),
    // así que cuanto más corto el nombre original, más margen para no pasarse
    // de los 255 caracteres de imports.file_path.
    $file = UploadedFile::fake()->createWithContent('c.xlsx', fixtureContents('catalog-sample.xlsx'));

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
