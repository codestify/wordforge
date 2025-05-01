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
     * Bootstrap any application services.
     */
    public function boot(): void
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
     */
    protected function registerService(string $name, callable $factory): void
    {
        ServiceManager::register($name, $factory);
    }

    /**
     * Register any application services.
     */
    abstract public function register(): void;

    /**
     * Helper to register a singleton
     */
    protected function registerSingleton(string $name, callable $factory): void
    {
        ServiceManager::singleton($name, $factory);
    }
}
