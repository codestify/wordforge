<?php

namespace WordForge;

use WordForge\Support\ServiceProvider;

/**
 * Manages service providers for WordForge
 *
 * Handles registration and booting of service providers,
 * integrated with WordPress hooks.
 *
 * @package WordForge
 */
class ServiceProviderManager
{
    /**
     * Registered providers
     */
    protected static array $providers = [];

    /**
     * Providers that have been registered
     */
    protected static array $registered = [];

    /**
     * Providers that have been booted
     */
    protected static array $booted = [];

    /**
     * Register service providers
     *
     * @param  array  $providers  Class names of service providers
     */
    public static function register(array $providers): void
    {
        self::$providers = array_merge(self::$providers, $providers);

        // Set up the initial hooks for each provider
        foreach ($providers as $provider) {
            if (! class_exists($provider)) {
                continue;
            }

            $instance = new $provider();
            if (! ($instance instanceof ServiceProvider)) {
                continue;
            }

            $hooks = $instance->hooks();

            foreach ($hooks as $hook => $priority) {
                add_action($hook, function () use ($provider) {
                    self::initializeProvider($provider);
                }, $priority);
            }
        }

        // Boot after all providers are registered
        add_action('wp_loaded', [self::class, 'bootProviders'], 1);
    }

    /**
     * Initialize a provider (register)
     */
    protected static function initializeProvider(string $provider): void
    {
        // Skip if already registered
        if (isset(self::$registered[$provider])) {
            return;
        }

        self::$registered[$provider] = new $provider();
        self::$registered[$provider]->register();
    }

    /**
     * Boot all registered providers
     */
    public static function bootProviders(): void
    {
        foreach (self::$registered as $provider => $instance) {
            // Skip if already booted
            if (isset(self::$booted[$provider])) {
                continue;
            }

            $instance->boot();
            self::$booted[$provider] = true;
        }
    }

    /**
     * Get all registered providers
     */
    public static function getProviders(): array
    {
        return self::$providers;
    }

    /**
     * Get all registered provider instances
     */
    public static function getRegisteredProviders(): array
    {
        return self::$registered;
    }
}
