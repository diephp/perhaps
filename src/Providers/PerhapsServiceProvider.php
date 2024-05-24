<?php

namespace DiePHP\Perhaps\Providers;

use DiePHP\Perhaps\Services\PerhapsService;
use DiePHP\Perhaps\Facades\Perhaps;
use Exception;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class PerhapsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Perhaps::class, function () {
            return app(PerhapsService::class, [
                app(LoggerInterface::class),
                []
            ]);
        });
    }


    public function provides()
    {
        return [
            Perhaps::class,
        ];
    }


}
