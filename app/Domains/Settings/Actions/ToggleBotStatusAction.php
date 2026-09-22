<?php

declare(strict_types=1);

namespace App\Domains\Settings\Actions;

use App\Domains\Settings\Models\BotSetting;
use Illuminate\Contracts\Auth\Authenticatable;

class ToggleBotStatusAction
{
    public function execute(bool $enabled, ?Authenticatable $actor = null): BotSetting
    {
        $setting = BotSetting::current();

        $setting->update([
            'enabled' => $enabled,
            'paused_at' => $enabled ? null : now(),
            'paused_by' => $enabled ? null : $actor?->getAuthIdentifier(),
        ]);

        return $setting->fresh();
    }
}
