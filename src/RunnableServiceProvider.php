<?php

namespace DirectoryTree\Runnable;

use Illuminate\Support\ServiceProvider;

class RunnableServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->app->singleton(Runner::class, fn ($app) => new Runner($app));
    }
}
