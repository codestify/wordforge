<?php

namespace WordForge\Support;

/**
 * Simple service manager for WordForge
 * 
 * Provides a lightweight service locator pattern for registering
 * and retrieving services without a full dependency injection container.
 *
 * @package WordForge\Support
 */
class ServiceManager
{
    /**
     * Registered services
     * @var array
     */
    protected static $services = [];
    
    /**
     * Resolved instances
     * @var array
     */
    protected static $instances = [];
    
    /**
     * Register a service factory
     * 
     * @param string $name
     * @param callable $factory
     * @return void
     */
    public static function register($name, callable $factory)
    {
        self::$services[$name] = $factory;
    }
    
    /**
     * Register a singleton service
     * 
     * @param string $name
     * @param callable $factory
     * @return void
     */
    public static function singleton($name, callable $factory)
    {
        self::register($name, function(...$args) use ($factory, $name) {
            if (!isset(self::$instances[$name])) {
                self::$instances[$name] = $factory(...$args);
            }
            return self::$instances[$name];
        });
    }
    
    /**
     * Check if a service exists
     * 
     * @param string $name
     * @return bool
     */
    public static function has($name)
    {
        return isset(self::$services[$name]);
    }
    
    /**
     * Get a service
     * 
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public static function get($name, ...$params)
    {
        if (!self::has($name)) {
            throw new \Exception("Service '$name' not registered");
        }
        
        return call_user_func_array(self::$services[$name], $params);
    }
    
    /**
     * Set an instance directly
     * 
     * @param string $name
     * @param mixed $instance
     * @return void
     */
    public static function instance($name, $instance)
    {
        self::$instances[$name] = $instance;
        
        self::singleton($name, function() use ($instance) {
            return $instance;
        });
    }
    
    /**
     * Clear all services (mainly for testing)
     * 
     * @return void
     */
    public static function clear()
    {
        self::$services = [];
        self::$instances = [];
    }
}
