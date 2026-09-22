<?php

declare(strict_types=1);

use App\Domains\Catalog\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Domains\Catalog\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Domains\Catalog\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
use App\Domains\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lista los productos existentes', function () {
    $product = Product::factory()->create(['name' => 'Auriculares Bluetooth XT-200']);

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$product])
        ->assertSee('Auriculares Bluetooth XT-200');
});

it('crea un producto desde el formulario y encola la generación del embedding', function () {
    Queue::fake();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Mochila Antirrobo 25L',
            'category' => 'Accesorios',
            'description' => 'Mochila con puerto USB',
            'price' => 165000,
            'stock' => 15,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::sole();

    expect($product->name)->toBe('Mochila Antirrobo 25L')
        ->and($product->stock)->toBe(15);

    Queue::assertPushed(GenerateProductEmbeddingJob::class, fn (GenerateProductEmbeddingJob $job) => $job->product->is($product)
    );
});

it('no crea el producto si falta el nombre', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => '',
            'price' => 100000,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);

    expect(Product::count())->toBe(0);
});

it('no crea el producto si falta el precio', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Producto sin precio',
            'price' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['price' => 'required']);

    expect(Product::count())->toBe(0);
});

it('edita un producto y vuelve a encolar la generación del embedding', function () {
    Queue::fake();

    $product = Product::factory()->create(['name' => 'Mouse Inalámbrico Slim', 'price' => 65000, 'stock' => 50]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'name' => 'Mouse Inalámbrico Slim Pro',
            'price' => 75000,
            'stock' => 40,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $product->refresh();

    expect($product->name)->toBe('Mouse Inalámbrico Slim Pro')
        ->and((float) $product->price)->toBe(75000.0)
        ->and($product->stock)->toBe(40);

    Queue::assertPushed(GenerateProductEmbeddingJob::class, fn (GenerateProductEmbeddingJob $job) => $job->product->is($product)
    );
});

it('borra un producto', function () {
    $product = Product::factory()->create();

    Livewire::test(ListProducts::class)
        ->callTableAction('delete', $product);

    expect(Product::find($product->id))->toBeNull();
});

it('filtra productos por categoría', function () {
    $audio = Product::factory()->create(['category' => 'Audio']);
    $calzado = Product::factory()->create(['category' => 'Calzado']);

    Livewire::test(ListProducts::class)
        ->filterTable('category', 'Audio')
        ->assertCanSeeTableRecords([$audio])
        ->assertCanNotSeeTableRecords([$calzado]);
});
