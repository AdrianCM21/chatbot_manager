<?php

declare(strict_types=1);

namespace App\Domains\Settings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSetting extends Model
{
    protected $fillable = [
        'enabled',
        'paused_at',
        'paused_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'paused_at' => 'datetime',
        ];
    }

    public function pausedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paused_by');
    }

    /**
     * Fila única de configuración (patrón singleton).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['enabled' => true]);
    }

    public static function isEnabled(): bool
    {
        return (bool) static::current()->enabled;
    }
}
