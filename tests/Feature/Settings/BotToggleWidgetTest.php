<?php

declare(strict_types=1);

use App\Domains\Settings\Filament\Widgets\BotStatusWidget;
use App\Domains\Settings\Models\BotSetting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('pausa el bot al ejecutar la acción del widget', function () {
    expect(BotSetting::isEnabled())->toBeTrue();

    Livewire::test(BotStatusWidget::class)
        ->mountAction('toggleBot')
        ->callMountedAction();

    expect(BotSetting::isEnabled())->toBeFalse();

    $setting = BotSetting::current();
    expect($setting->paused_at)->not->toBeNull()
        ->and($setting->paused_by)->toBe($this->user->id);
});

it('reactiva el bot al ejecutar la acción de nuevo', function () {
    BotSetting::current()->update(['enabled' => false, 'paused_at' => now()]);

    Livewire::test(BotStatusWidget::class)
        ->mountAction('toggleBot')
        ->callMountedAction();

    expect(BotSetting::isEnabled())->toBeTrue();

    $setting = BotSetting::current();
    expect($setting->paused_at)->toBeNull()
        ->and($setting->paused_by)->toBeNull();
});
