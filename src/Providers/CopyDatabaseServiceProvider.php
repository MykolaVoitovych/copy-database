<?php

namespace Mykolavoitovych\CopyDatabase\Providers;

use Illuminate\Support\ServiceProvider;
use Mykolavoitovych\CopyDatabase\CopyDatabase;

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
            ]);
        }
    }
}
