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
        $this->app->singleton(Perhaps::class, function ($app) {
            return new PerhapsService(
                $app->make(LoggerInterface::class),
                $app['config']->get('perhaps.errorLogType', 'warning'),
                $app['config']->get('perhaps.excludeExceptions', [])
            );
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
