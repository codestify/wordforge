<?php

namespace WordForge\Support\Facades;

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
     * @return object
     */
    public static function getFacadeInstance()
    {
        $accessor = static::getFacadeAccessor();

        if (isset(static::$resolvedInstances[$accessor])) {
            return static::$resolvedInstances[$accessor];
        }

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

        return $instance->$method(...$args);
    }
}
