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
     * @var array
     */
    protected static $providers = [];
    
    /**
     * Providers that have been registered
     * @var array
     */
    protected static $registered = [];
    
    /**
     * Providers that have been booted
     * @var array
     */
    protected static $booted = [];
    
    /**
     * Register service providers
     * 
     * @param array $providers Class names of service providers
     * @return void
     */
    public static function register(array $providers)
    {
        self::$providers = array_merge(self::$providers, $providers);
        
        // Set up the initial hooks for each provider
        foreach ($providers as $provider) {
            if (!class_exists($provider)) {
                continue;
            }
            
            $instance = new $provider();
            if (!($instance instanceof ServiceProvider)) {
                continue;
            }
            
            $hooks = $instance->hooks();
            
            foreach ($hooks as $hook => $priority) {
                add_action($hook, function() use ($provider) {
                    self::initializeProvider($provider);
                }, $priority);
            }
        }
        
        // Boot after all providers are registered
        add_action('wp_loaded', [self::class, 'bootProviders'], 1);
    }
    
    /**
     * Initialize a provider (register)
     * 
     * @param string $provider
     * @return void
     */
    protected static function initializeProvider($provider)
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
     * 
     * @return void
     */
    public static function bootProviders()
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
     * 
     * @return array
     */
    public static function getProviders()
    {
        return self::$providers;
    }
    
    /**
     * Get all registered provider instances
     * 
     * @return array
     */
    public static function getRegisteredProviders()
    {
        return self::$registered;
    }
}
