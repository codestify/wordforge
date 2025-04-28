<?php

namespace WordForge\Support\Facades;

use WordForge\Support\ServiceManager;

/**
 * Base Facade class for static proxy to classes
 *
 * @package WordForge\Support\Facades
 */
abstract class Facade
{
    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstances = [];

    /**
     * Get the facade accessor.
     *
     * @return string
     */
    abstract protected static function getFacadeAccessor();

    /**
     * Get the resolved instance.
     *
     * @return mixed
     */
    public static function getFacadeInstance()
    {
        $accessor = static::getFacadeAccessor();

        // First check if this facade is already resolved
        if (isset(static::$resolvedInstances[$accessor])) {
            return static::$resolvedInstances[$accessor];
        }

        // Then check if it's available in the service manager
        if (ServiceManager::has($accessor)) {
            $instance = ServiceManager::get($accessor);
            static::$resolvedInstances[$accessor] = $instance;
            return $instance;
        }

        // Finally, fall back to the old way of creating an instance
        $instance = static::createFacadeInstance($accessor);
        static::$resolvedInstances[$accessor] = $instance;
        return $instance;
    }

    /**
     * Create a facade instance.
     *
     * @param string $accessor
     * @return object
     */
    protected static function createFacadeInstance(string $accessor)
    {
        return new $accessor();
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        $instance = static::getFacadeInstance();
        
        // Handle cases where facade accessor is a class name with static methods
        if (is_string($instance) && method_exists($instance, $method)) {
            return forward_static_call_array([$instance, $method], $args);
        }

        return $instance->$method(...$args);
    }
}
