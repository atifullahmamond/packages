<?php

namespace Atifullahmamond\FilamentMeet;

use Atifullahmamond\FilamentMeet\Console\FilamentMeetInstallCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Atifullahmamond\FilamentMeet\Models\Meeting;
use Atifullahmamond\FilamentMeet\Policies\MeetingPolicy;
use Atifullahmamond\FilamentMeet\Services\MeetingService;

class FilamentMeetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-meet.php',
            'filament-meet'
        );

        $this->app->singleton(MeetingService::class, function ($app) {
            return new MeetingService();
        });
    }

    public function boot(): void
    {
        Livewire::addNamespace('filament-meet', classNamespace: 'Atifullahmamond\\FilamentMeet\\Livewire');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-meet');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'filament-meet');

        $this->registerRoutes();
        $this->registerPolicies();
        $this->publishAssets();

        if ($this->app->runningInConsole()) {
            $this->commands([
                FilamentMeetInstallCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix(config('filament-meet.route_prefix', 'meet'))
            ->name('filament-meet.')
            ->group(function () {
                Route::get('/room/{meeting:uuid}', [\Atifullahmamond\FilamentMeet\Http\Controllers\MeetingRoomController::class, 'show'])
                    ->name('room');
            });
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Meeting::class, MeetingPolicy::class);
    }

    protected function publishAssets(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/filament-meet.php' => config_path('filament-meet.php'),
            ], 'filament-meet-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'filament-meet-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/filament-meet'),
            ], 'filament-meet-views');
        }
    }
}
