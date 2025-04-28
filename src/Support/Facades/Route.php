<?php

namespace WordForge\Support\Facades;

/**
 * Route Facade
 *
 * @method static \WordForge\Http\Router\Route get(string $uri, mixed $action)
 * @method static \WordForge\Http\Router\Route post(string $uri, mixed $action)
 * @method static \WordForge\Http\Router\Route put(string $uri, mixed $action)
 * @method static \WordForge\Http\Router\Route patch(string $uri, mixed $action)
 * @method static \WordForge\Http\Router\Route delete(string $uri, mixed $action)
 * @method static \WordForge\Http\Router\Route any(string $uri, mixed $action)
 * @method static void resource(string $name, string $controller, array $options = [])
 * @method static void group(array $attributes, callable $callback)
 *
 * @package WordForge\Support\Facades
 */
class Route extends Facade
{
    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        return forward_static_call_array([\WordForge\Http\Router\Router::class, $method], $args);
    }

    /**
     * Get the facade accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \WordForge\Http\Router\Router::class;
    }
}
