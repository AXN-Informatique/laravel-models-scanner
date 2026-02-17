<?php

declare(strict_types=1);

namespace Axn\ModelsScanner;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    private string $basePath = '';

    public function register(): void
    {
        $this->basePath = __DIR__.'/../';

        $this->registerConfig();
    }

    public function boot(): void
    {
        if (! $this->app->isLocal()) {
            return;
        }

        $this->loadRoutesFrom($this->basePath.'routes/web.php');

        $this->loadViewsFrom([
            $this->basePath.'resources/views',
        ], 'models-scanner');

        if ($this->app->runningInConsole()) {
            // $this->commands([

            // ]);

            $this->configurePublishing();
        }
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(
            $this->basePath.'config/models-scanner.php',
            'models-scanner'
        );
    }

    private function configurePublishing(): void
    {
        $this->publishes([
            $this->basePath.'config/models-scanner.php' => $this->app->configPath('models-scanner.php'),
        ], 'models-scanner-config');
    }
}
