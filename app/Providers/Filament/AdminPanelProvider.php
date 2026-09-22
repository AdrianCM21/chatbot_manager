<?php

namespace App\Providers\Filament;

use App\Domains\Catalog\Filament\Resources\ProductResource;
use App\Domains\Settings\Filament\Pages\ConnectionSettings;
use App\Domains\Settings\Filament\Pages\Dashboard;
use App\Domains\Settings\Filament\Widgets\BotMetricsOverviewWidget;
use App\Domains\Settings\Filament\Widgets\BotStatusWidget;
use App\Domains\Settings\Filament\Widgets\MessagesChartWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->spa()
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->resources([
                // Los recursos viven junto a su dominio (app/Domains/{Dominio}/Filament/Resources)
                // en vez de la carpeta por defecto de Filament, y se registran acá explícitamente.
                ProductResource::class,
            ])
            ->pages([
                Dashboard::class,
                ConnectionSettings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Solo lo que le sirve al dueño de la tienda: nada de branding/widgets de
                // ejemplo de Filament (cuenta, versión, GitHub), el dashboard es 100% el bot.
                BotStatusWidget::class,
                BotMetricsOverviewWidget::class,
                MessagesChartWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
