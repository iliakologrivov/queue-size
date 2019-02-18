<?php

namespace Iliakologrivov\Queuesize;

use Illuminate\Support\ServiceProvider;

class QueueSizeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/queuesize.php' => config_path('queuesize.php')
        ]);

        $this->commands([QueueSizeCommand::class]);
    }
}
