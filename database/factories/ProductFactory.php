<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'category' => fake()->randomElement(['Audio', 'Calzado', 'Accesorios', 'Electrónica']),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(10000, 900000),
            'stock' => fake()->numberBetween(0, 50),
            'image_path' => null,
            'embedding' => null,
        ];
    }

    /**
     * Producto con un embedding ya generado (para tests de búsqueda semántica).
     *
     * @param  array<int, float>|null  $vector
     */
    public function withEmbedding(?array $vector = null): static
    {
        $dimensions = (int) config('services.deepseek.embedding_dimensions', 1536);

        return $this->state(fn (array $attributes) => [
            'embedding' => $vector ?? array_map(
                fn () => fake()->randomFloat(4, -1, 1),
                range(1, $dimensions),
            ),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
