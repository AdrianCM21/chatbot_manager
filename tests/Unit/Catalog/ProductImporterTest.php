<?php

declare(strict_types=1);

use App\Domains\Catalog\Imports\ProductImporter;
use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
use App\Domains\Catalog\Models\Product;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

/**
 * columnMap simula lo que arma la UI de Filament: mapea el nombre de columna
 * interno de ProductImporter (name, category, price, stock, description) a la
 * cabecera real del archivo subido, que puede venir con otro nombre.
 */
function makeProductImport(): Import
{
    $import = new Import;
    $import->user()->associate(User::factory()->create());
    $import->file_name = 'catalogo.xlsx';
    $import->file_path = 'imports/catalogo.xlsx';
    $import->importer = ProductImporter::class;
    $import->total_rows = 1;
    $import->save();

    return $import;
}

beforeEach(function () {
    Queue::fake();
    Redis::shouldReceive('incr')->byDefault();
});

it('mapea columnas del archivo con nombres distintos a los campos del producto', function () {
    $import = makeProductImport();

    // El archivo real trae "Producto", "Rubro", "Valor", "Existencia", "Detalle"
    // en vez de name/category/price/stock/description.
    $columnMap = [
        'name' => 'Producto',
        'category' => 'Rubro',
        'price' => 'Valor',
        'stock' => 'Existencia',
        'description' => 'Detalle',
    ];

    $importer = new ProductImporter($import, $columnMap, []);

    $importer([
        'Producto' => 'Auriculares Bluetooth XT-200',
        'Rubro' => 'Audio',
        'Valor' => '185000',
        'Existencia' => '24',
        'Detalle' => 'Auriculares inalámbricos',
    ]);

    $product = Product::sole();

    expect($product->name)->toBe('Auriculares Bluetooth XT-200')
        ->and($product->category)->toBe('Audio')
        ->and((float) $product->price)->toBe(185000.0)
        ->and($product->stock)->toBe(24)
        ->and($product->description)->toBe('Auriculares inalámbricos');

    Queue::assertPushed(GenerateProductEmbeddingJob::class, fn (GenerateProductEmbeddingJob $job) => $job->product->is($product)
    );
});

it('actualiza un producto existente en vez de duplicarlo cuando el nombre ya existe (sin importar mayúsculas)', function () {
    $existente = Product::factory()->create(['name' => 'Auriculares Bluetooth XT-200', 'stock' => 5]);

    $import = makeProductImport();
    $columnMap = ['name' => 'Producto', 'price' => 'Valor', 'stock' => 'Existencia'];
    $importer = new ProductImporter($import, $columnMap, []);

    $importer([
        'Producto' => 'auriculares bluetooth xt-200',
        'Valor' => '199000',
        'Existencia' => '30',
    ]);

    expect(Product::count())->toBe(1);

    $existente->refresh();
    expect($existente->stock)->toBe(30)
        ->and((float) $existente->price)->toBe(199000.0);
});

it('rechaza una fila sin nombre de producto', function () {
    $import = makeProductImport();
    $columnMap = ['name' => 'Producto', 'price' => 'Valor'];
    $importer = new ProductImporter($import, $columnMap, []);

    $importer([
        'Producto' => '',
        'Valor' => '165000',
    ]);
})->throws(ValidationException::class);

it('rechaza una fila con precio no numérico', function () {
    $import = makeProductImport();
    $columnMap = ['name' => 'Producto', 'price' => 'Valor'];
    $importer = new ProductImporter($import, $columnMap, []);

    $importer([
        'Producto' => 'Mouse Inalámbrico Slim',
        'Valor' => 'no-es-un-numero',
    ]);
})->throws(ValidationException::class);

it('usa el default de la columna (0) cuando el stock no viene mapeado', function () {
    $import = makeProductImport();
    $columnMap = ['name' => 'Producto', 'price' => 'Valor'];
    $importer = new ProductImporter($import, $columnMap, []);

    $importer([
        'Producto' => 'Producto sin stock mapeado',
        'Valor' => '50000',
    ]);

    expect(Product::sole()->stock)->toBe(0);
});
