<?php

namespace Dibakar\ActivityScope\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'activityscope:install';

    protected $description = 'Install and setup the Activity Scope package';

    public function handle()
    {
        $this->info('Installing Activity Scope...');

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--provider' => "Dibakar\ActivityScope\ActivityScopeServiceProvider",
            '--tag' => 'activityscope-config'
        ]);

        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--provider' => "Dibakar\ActivityScope\ActivityScopeServiceProvider",
            '--tag' => 'activityscope-migrations'
        ]);

        if ($this->confirm('Would you like to run migrations now?')) {
            $this->call('migrate');
        }

        $this->info('Activity Scope installed successfully!');
    }
}
