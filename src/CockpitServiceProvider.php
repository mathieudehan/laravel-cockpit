<?php

namespace Mathieu\Cockpit;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mathieu\Cockpit\Services\CommandInspector;
use Mathieu\Cockpit\Services\EventInspector;
use Mathieu\Cockpit\Services\JobManager;
use Mathieu\Cockpit\Services\LogParser;
use Mathieu\Cockpit\Services\RouteInspector;
use Mathieu\Cockpit\Services\ScheduleInspector;

class CockpitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cockpit.php', 'cockpit');

        $this->app->singleton(RouteInspector::class);
        $this->app->singleton(CommandInspector::class);
        $this->app->singleton(ScheduleInspector::class);
        $this->app->singleton(EventInspector::class);
        $this->app->singleton(LogParser::class);
        $this->app->singleton(JobManager::class);
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerGate();
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/cockpit.php' => config_path('cockpit.php'),
        ], 'cockpit-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/cockpit'),
        ], 'cockpit-views');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cockpit');
    }

    protected function registerRoutes(): void
    {
        if (! config('cockpit.enabled', true)) {
            return;
        }

        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/cockpit.php');
        });
    }

    protected function routeConfiguration(): array
    {
        return [
            'prefix'     => config('cockpit.path', 'cockpit'),
            'middleware' => config('cockpit.middleware', ['web']),
        ];
    }

    protected function registerGate(): void
    {
        Gate::define('viewCockpit', function ($user) {
            $allowed = config('cockpit.allowed_emails', []);

            if (empty($allowed)) {
                return true;
            }

            return in_array($user->email ?? '', $allowed, true);
        });
    }
}
