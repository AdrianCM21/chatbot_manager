<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class Product extends Model
{
    use HasFactory;
    use HasNeighbors;

    /**
     * Al vivir fuera de App\Models, Laravel no adivina la factory por
     * convención (buscaría Database\Factories\Domains\Catalog\Models\ProductFactory).
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected $fillable = [
        'name',
        'category',
        'description',
        'price',
        'stock',
        'image_path',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'embedding' => Vector::class,
        ];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Texto usado para generar el embedding semántico del producto.
     */
    public function embeddingSourceText(): string
    {
        return collect([$this->name, $this->category, $this->description])
            ->filter()
            ->implode(' — ');
    }
}
