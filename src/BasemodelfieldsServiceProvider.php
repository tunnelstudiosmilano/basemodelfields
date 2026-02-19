<?php

namespace Mardok9185\Basemodelfields;

use Illuminate\Support\ServiceProvider;

class BasemodelfieldsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $config_file = __DIR__ . '/config/basemodelfields.php';
        $this->mergeConfigFrom($config_file, 'basemodelfields');
        $this->publishes([
            $config_file => config_path('basemodelfields.php')
        ]);
        $this->loadViewsFrom(__DIR__ . '/views', 'basemodelfields');

        $this->publishes([
            __DIR__ . '/views' => base_path('resources/views/vendor/basemodelfields')
        ], 'views');
        $this->publishes([
            __DIR__ . '/public' => public_path('vendor/basemodelfields')
        ], 'public');

    }

    public function register()
    {
        $config_file = __DIR__ . '/config/basemodelfields.php';
        $this->mergeConfigFrom($config_file, 'basemodelfields');

        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('BaseModelFields', 'Mardok9185\Basemodelfields\Basemodelfields');
        });
    }

}