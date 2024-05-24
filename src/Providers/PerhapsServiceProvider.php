<?php

namespace DiePHP\Perhaps\Providers;

use DiePHP\Perhaps\Services\PerhapsService;
use DiePHP\Perhaps\Facades\Perhaps;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class PerhapsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Perhaps::class, function () {
            return app(PerhapsService::class, [
                app(LoggerInterface::class),
                config('perhaps.errorLogType', 'warning'),
                config('perhaps.excludeExceptions', [])
            ]);
        });
    }

    public function boot ()
    {
        $this->publishConfigs();
    }


    public function provides()
    {
        return [
            Perhaps::class,
        ];
    }

    protected function publishConfigs(): void
    {
        $this->publishes([
            __DIR__ . '/../config/perhaps.php' => config_path('perhaps.php'),
        ], 'perhaps');

    }


}
