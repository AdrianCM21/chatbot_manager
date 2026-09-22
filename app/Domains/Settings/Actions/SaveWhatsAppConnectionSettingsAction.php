<?php

declare(strict_types=1);

namespace App\Domains\Settings\Actions;

use App\Domains\Settings\Models\WhatsAppConnectionSetting;

class SaveWhatsAppConnectionSettingsAction
{
    /**
     * @param  array{access_token?: ?string, phone_number_id?: ?string, business_account_id?: ?string}  $data
     */
    public function execute(array $data, ?bool $justVerified = null): WhatsAppConnectionSetting
    {
        $setting = WhatsAppConnectionSetting::current();

        $setting->fill([
            'phone_number_id' => $data['phone_number_id'] ?? $setting->phone_number_id,
            'business_account_id' => $data['business_account_id'] ?? $setting->business_account_id,
        ]);

        // El campo del token viaja vacío en el form una vez guardado (nunca se muestra el valor real).
        // Solo se pisa si el usuario tipeó uno nuevo.
        if (filled($data['access_token'] ?? null)) {
            $setting->access_token = $data['access_token'];
        }

        if ($justVerified === true) {
            $setting->verified_at = now();
            $setting->last_verification_error = null;
        } elseif ($justVerified === false) {
            $setting->verified_at = null;
        }

        $setting->save();

        return $setting;
    }
}
