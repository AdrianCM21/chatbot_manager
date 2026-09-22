<?php

declare(strict_types=1);

use App\Domains\Catalog\Actions\SearchProductsBySemanticQueryAction;
use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Services\DeepSeekClient;
use Illuminate\Support\Facades\DB;

it('arma la query con el operador de distancia coseno sobre la columna embedding', function () {
    $deepSeek = Mockery::mock(DeepSeekClient::class);
    $deepSeek->shouldReceive('interpretSearchQuery')->once()
        ->with('tenés zapatillas negras?')
        ->andReturn('zapatillas negras');
    $deepSeek->shouldReceive('embed')->once()
        ->with('zapatillas negras')
        ->andReturn(array_fill(0, 8, 0.1));

    $this->instance(DeepSeekClient::class, $deepSeek);

    $capturedSql = null;
    $capturedBindings = null;

    DB::listen(function ($query) use (&$capturedSql, &$capturedBindings) {
        if (str_contains($query->sql, '"embedding"') || str_contains($query->sql, 'embedding')) {
            $capturedSql = $query->sql;
            $capturedBindings = $query->bindings;
        }
    });

    app(SearchProductsBySemanticQueryAction::class)->execute('tenés zapatillas negras?');

    expect($capturedSql)->not->toBeNull()
        ->and($capturedSql)->toContain('<=>')
        ->and($capturedSql)->toContain('embedding')
        ->and($capturedSql)->toContain('limit')
        ->and($capturedBindings)->not->toBeEmpty();
});

it('respeta el límite de resultados pedido', function () {
    $this->instance(DeepSeekClient::class, Mockery::mock(DeepSeekClient::class, function ($mock) {
        $mock->shouldReceive('interpretSearchQuery')->andReturnUsing(fn (string $q) => $q);
        $mock->shouldReceive('embed')->andReturn(array_fill(0, 8, 0.1));
    }));

    Product::factory()->count(5)->withEmbedding()->create();

    $results = app(SearchProductsBySemanticQueryAction::class)->execute('cualquier cosa', limit: 2);

    expect($results)->toHaveCount(2);
});

it('devuelve los productos ordenados del más cercano al más lejano según el embedding', function () {
    $this->instance(DeepSeekClient::class, Mockery::mock(DeepSeekClient::class, function ($mock) {
        $mock->shouldReceive('interpretSearchQuery')->andReturnUsing(fn (string $q) => $q);
        $mock->shouldReceive('embed')->andReturn([1, 0, 0, 0, 0, 0, 0, 0]);
    }));

    $lejano = Product::factory()->withEmbedding([0, 1, 0, 0, 0, 0, 0, 0])->create(['name' => 'Producto lejano']);
    $cercano = Product::factory()->withEmbedding([1, 0, 0, 0, 0, 0, 0, 0])->create(['name' => 'Producto cercano']);

    $results = app(SearchProductsBySemanticQueryAction::class)->execute('busqueda');

    expect($results->first()->id)->toBe($cercano->id)
        ->and($results->last()->id)->toBe($lejano->id);
});

it('no incluye productos sin embedding todavía generado', function () {
    $this->instance(DeepSeekClient::class, Mockery::mock(DeepSeekClient::class, function ($mock) {
        $mock->shouldReceive('interpretSearchQuery')->andReturnUsing(fn (string $q) => $q);
        $mock->shouldReceive('embed')->andReturn(array_fill(0, 8, 0.1));
    }));

    Product::factory()->create(['name' => 'Sin embedding', 'embedding' => null]);
    $conEmbedding = Product::factory()->withEmbedding()->create(['name' => 'Con embedding']);

    $results = app(SearchProductsBySemanticQueryAction::class)->execute('busqueda');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($conEmbedding->id);
});
