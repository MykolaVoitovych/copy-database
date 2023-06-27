<?php

namespace Mykolavoitovych\CopyDatabase\Providers;

use Illuminate\Support\ServiceProvider;
use Mykolavoitovych\CopyDatabase\CopyDatabase;
use Mykolavoitovych\CopyDatabase\ImportDbJobsCommand;

class CopyDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/copy-database.php', 'copy-database'
        );
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyDatabase::class,
                ImportDbJobsCommand::class
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/copy-database.php' => config_path('copy-database.php')
        ], 'copy-database');
    }
}
