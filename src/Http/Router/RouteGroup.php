<?php

namespace WordForge\Http\Router;

/**
 * RouteGroup class for handling route groups
 *
 * @package WordForge\Http\Router
 */
class RouteGroup
{
    /**
     * The attributes for the route group.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Create a new route group instance.
     *
     * @param  array  $attributes
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get a specific attribute from the group.
     *
     * @param  string  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function getAttribute(string $key, $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get all attributes from the group.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Merge the group attributes with the route attributes.
     *
     * @param  array  $routeAttributes
     *
     * @return array
     */
    public function mergeWithRoute(array $routeAttributes): array
    {
        $merged = $routeAttributes;

        // Handle prefix
        if (isset($this->attributes['prefix'])) {
            $prefix = trim($this->attributes['prefix'], '/');

            if (isset($routeAttributes['prefix'])) {
                $routePrefix      = trim($routeAttributes['prefix'], '/');
                $merged['prefix'] = $prefix . '/' . $routePrefix;
            } else {
                $merged['prefix'] = $prefix;
            }
        }

        // Handle middleware
        if (isset($this->attributes['middleware'])) {
            $middleware = (array)$this->attributes['middleware'];

            if (isset($routeAttributes['middleware'])) {
                $routeMiddleware      = (array)$routeAttributes['middleware'];
                $merged['middleware'] = array_merge($middleware, $routeMiddleware);
            } else {
                $merged['middleware'] = $middleware;
            }
        }

        // Handle namespace
        if (isset($this->attributes['namespace'])) {
            $namespace = $this->attributes['namespace'];

            if (isset($routeAttributes['namespace'])) {
                $routeNamespace = $routeAttributes['namespace'];

                if (str_starts_with($routeNamespace, '\\')) {
                    $merged['namespace'] = $routeNamespace;
                } else {
                    $merged['namespace'] = $namespace . '\\' . $routeNamespace;
                }
            } else {
                $merged['namespace'] = $namespace;
            }
        }

        return $merged;
    }
}
