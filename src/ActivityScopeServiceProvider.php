<?php

namespace Dibakar\ActivityScope;

use Illuminate\Support\ServiceProvider;
use Dibakar\ActivityScope\Console\InstallCommand;
use Dibakar\ActivityScope\Services\ActivityBuilder;
use Dibakar\ActivityScope\Services\MessageBuilder;
use Dibakar\ActivityScope\Support\DataSanitizer;
use Dibakar\ActivityScope\Support\NameResolver;

class ActivityScopeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/activityscope.php' => config_path('activityscope.php'),
        ], 'activityscope-config');

        $this->publishes([
            __DIR__ . '/../migrations/' => database_path('migrations'),
        ], 'activityscope-migrations');

        $this->publishes([
            __DIR__ . '/../config/activityscope.php' => config_path('activityscope.php'),
            __DIR__ . '/../migrations/' => database_path('migrations'),
        ], 'activityscope-assets');

        $this->loadMigrationsFrom(
            __DIR__ . '/../migrations'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/activityscope.php',
            'activityscope'
        );

        $this->registerServices();

        require_once __DIR__ . '/helpers.php';
    }

    protected function registerServices(): void
    {
        $this->app->singleton('activityscope.names', function () {
            return new NameResolver;
        });

        $this->app->singleton('activityscope.message', function ($app) {
            return new MessageBuilder($app['activityscope.names']);
        });

        $this->app->bind('activityscope.activity', function ($app) {
            return new ActivityBuilder($app->make(DataSanitizer::class));
        });

        $this->app->alias('activityscope.activity', 'activityscope');
    }

    public function provides(): array
    {
        return [
            'activityscope.message',
            'activityscope.activity',
            'activityscope.names',
            'activityscope',
        ];
    }
}
