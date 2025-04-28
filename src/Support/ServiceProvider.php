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
    
    /**
     * Get the WordPress hooks that should trigger this service provider
     * 
     * @return array [hook_name => priority]
     */
    public function hooks(): array
    {
        // Default to running on 'init' hook with priority 10
        return ['init' => 10];
    }
    
    /**
     * Helper to register a service
     * 
     * @param string $name
     * @param callable $factory
     * @return void
     */
    protected function registerService($name, callable $factory)
    {
        ServiceManager::register($name, $factory);
    }
    
    /**
     * Helper to register a singleton
     * 
     * @param string $name
     * @param callable $factory
     * @return void
     */
    protected function registerSingleton($name, callable $factory)
    {
        ServiceManager::singleton($name, $factory);
    }
}
