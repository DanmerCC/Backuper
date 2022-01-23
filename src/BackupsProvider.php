<?php

namespace DanmerCC\Backuper;

use DanmerCC\Backuper\Console\RunBackup;
use Illuminate\Support\ServiceProvider;

class BackupsProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $path = $this->getConfigPath();
        $this->publishes([$path => config_path('backups.php')], 'config');
        /*$this->app->singleton(Connection::class, function ($app) {
            return new Connection(config('riak'));
        });*/
    }

    public function boot()
    {

        $this->commands([RunBackup::class]);
    }

    public function getConfigPath()
    {
        return __DIR__ . "/config/backups.php";
    }
}