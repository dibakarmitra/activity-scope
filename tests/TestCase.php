<?php

namespace Dibakar\ActivityScope\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Dibakar\ActivityScope\ActivityScopeServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActivityScopeServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('activityscope.enabled', true);
        $app['config']->set('activityscope.table_name', 'activities');
        $app['config']->set('activityscope.mappings', []);
        $app['config']->set('activityscope.error_handling.throw_on_error', true);
    }

    protected function setUpDatabase()
    {
        $migration = include __DIR__ . '/../migrations/2025_12_20_000000_create_activities_table.php';
        $migration->up();
    }
}
