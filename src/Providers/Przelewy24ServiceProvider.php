<?php

namespace Przelewy24\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Przelewy24\Przelewy24;

class Przelewy24ServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->przelewy24ConfigPath(), 'przelewy24');

        $this->app->singleton(Przelewy24::class, function (Application $app): Przelewy24 {
            $config = $app['config']['przelewy24'];

            return new Przelewy24(
                $config['merchant_id'],
                $config['reports_key'],
                $config['crc'],
                $config['is_live'],
                $config['pos_id']
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            $this->przelewy24ConfigPath() => $this->przelewy24ConfigPublishPath(),
        ], 'przelewy24');
    }

    public function provides(): array
    {
        return [Przelewy24::class];
    }

    private function przelewy24ConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/config/przelewy24.php';
    }

    private function przelewy24ConfigPublishPath(): string
    {
        if (function_exists('config_path')) {
            return config_path('przelewy24.php');
        }

        return 'config/przelewy24.php';
    }
}
