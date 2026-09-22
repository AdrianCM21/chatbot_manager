<?php

declare(strict_types=1);

use App\Domains\Catalog\Models\Product;
use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * Requiere: servidor real, Postgres+pgvector, Redis, ChromeDriver, y la
 * extensión ext-zip (Filament necesita poder leer el .xlsx real). No se
 * pudo ejecutar en el sandbox de desarrollo por faltar todo lo anterior.
 */
it('sube un catálogo en xlsx desde el navegador y los productos aparecen en el listado', function () {
    $user = User::factory()->create();
    $fixturePath = base_path('tests/Fixtures/catalog-sample.xlsx');

    $this->browse(function (Browser $browser) use ($user, $fixturePath) {
        $browser->loginAs($user)
            ->visit('/admin/products')
            ->waitForText('Productos')
            ->press('Importar catálogo')
            ->waitForText('Importar Productos')
            ->attach('input[type="file"]', $fixturePath)
            ->waitForText('Mapeo de columnas')
            // El auto-mapeo por nombre de columna (Producto, Rubro, Valor,
            // Existencia, Detalle) ya debería resolver los 5 campos solo;
            // acá solo confirmamos que la UI los muestra antes de importar.
            ->assertSee('Nombre')
            ->assertSee('Categoría')
            ->assertSee('Precio')
            ->press('Importar')
            ->waitForText('completad', 30) // "completada"/"se completó" según la traducción activa
            ->visit('/admin/products')
            ->waitForText('Auriculares Bluetooth XT-200')
            ->assertSee('Auriculares Bluetooth XT-200')
            ->assertSee('Zapatillas Urbanas Runner');
    });

    expect(Product::where('name', 'Auriculares Bluetooth XT-200')->exists())->toBeTrue();

    // Las dos filas inválidas del fixture (sin nombre / precio no numérico)
    // no deberían haber creado productos.
    expect(Product::count())->toBe(2);
});
