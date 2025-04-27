<?php

namespace WordForge\Support;

/**
 * Base Service Provider class
 *
 * @package WordForge\Support
 */
abstract class ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
