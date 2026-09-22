<?php

declare(strict_types=1);

use App\Domains\Settings\Actions\ToggleBotStatusAction;
use App\Domains\Settings\Models\BotSetting;
use App\Models\User;

it('está activo por defecto (seed de la migración)', function () {
    expect(BotSetting::isEnabled())->toBeTrue();
});

it('isEnabled refleja el valor guardado', function () {
    BotSetting::current()->update(['enabled' => false]);

    expect(BotSetting::isEnabled())->toBeFalse();

    BotSetting::current()->update(['enabled' => true]);

    expect(BotSetting::isEnabled())->toBeTrue();
});

it('ToggleBotStatusAction pausa el bot y registra quién lo pausó', function () {
    $user = User::factory()->create();

    $setting = app(ToggleBotStatusAction::class)->execute(false, $user);

    expect($setting->enabled)->toBeFalse()
        ->and($setting->paused_at)->not->toBeNull()
        ->and($setting->paused_by)->toBe($user->id)
        ->and(BotSetting::isEnabled())->toBeFalse();
});

it('ToggleBotStatusAction reactiva el bot y limpia los datos de pausa', function () {
    $user = User::factory()->create();
    app(ToggleBotStatusAction::class)->execute(false, $user);

    $setting = app(ToggleBotStatusAction::class)->execute(true, $user);

    expect($setting->enabled)->toBeTrue()
        ->and($setting->paused_at)->toBeNull()
        ->and($setting->paused_by)->toBeNull();
});
