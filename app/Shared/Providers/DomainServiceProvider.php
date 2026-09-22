<?php

declare(strict_types=1);

namespace App\Shared\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

abstract class DomainServiceProvider extends ServiceProvider
{
    /**
     * Absolute path to the domain's root directory (pass __DIR__ from the child).
     */
    abstract protected function domainPath(): string;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->registerPolicies();
        $this->registerEvents();
        $this->loadMigrations();
    }

    protected function loadRoutes(): void
    {
        $routes = $this->domainPath().'/routes.php';

        if (is_file($routes)) {
            $this->loadRoutesFrom($routes);
        }
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [];
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies() as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * @return array<class-string, array<int, class-string>>
     */
    protected function events(): array
    {
        return [];
    }

    protected function registerEvents(): void
    {
        $dispatcher = $this->app->make('events');

        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    protected function migrationsPaths(): array
    {
        $default = $this->domainPath().'/Database/Migrations';

        return is_dir($default) ? [$default] : [];
    }

    protected function loadMigrations(): void
    {
        $paths = $this->migrationsPaths();

        if ($paths !== []) {
            $this->loadMigrationsFrom($paths);
        }
    }
}
