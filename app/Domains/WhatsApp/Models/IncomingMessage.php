<?php

declare(strict_types=1);

namespace App\Domains\WhatsApp\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class IncomingMessage extends Model
{
    protected $fillable = [
        'from_number',
        'type',
        'body',
        'matched_products_count',
    ];

    protected function casts(): array
    {
        return [
            'matched_products_count' => 'integer',
        ];
    }

    public function hadNoResults(): bool
    {
        return $this->matched_products_count === 0;
    }

    public function scopeReceivedToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeReceivedSince(Builder $query, Carbon $since): Builder
    {
        return $query->where('created_at', '>=', $since);
    }

    public function scopeWithoutResults(Builder $query): Builder
    {
        return $query->where('matched_products_count', 0);
    }
}
