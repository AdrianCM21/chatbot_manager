<?php

declare(strict_types=1);

namespace App\Domains\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConnectionSetting extends Model
{
    protected $table = 'whatsapp_connection_settings';

    protected $fillable = [
        'access_token',
        'phone_number_id',
        'business_account_id',
        'verified_at',
        'last_verification_error',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Fila única de configuración (patrón singleton).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    public function isConfigured(): bool
    {
        return filled($this->access_token) && filled($this->phone_number_id);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Nunca se muestra el token completo: solo los últimos 4 caracteres.
     */
    public function maskedAccessToken(): ?string
    {
        if (blank($this->access_token)) {
            return null;
        }

        return '•••• •••• '.substr($this->access_token, -4);
    }
}
