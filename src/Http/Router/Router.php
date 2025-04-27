<?php

namespace WordForge\Http\Router;

/**
 * Router class for managing WordPress REST API routes with Laravel-style syntax
 *
 * Provides a full-featured Laravel-inspired routing interface for WordPress REST API.
 *
 * @package WordForge\Http\Router
 */
class Router
{
    /**
     * The route collection instance.
     *
     * @var RouteCollection
     */
    public static $routes;

    /**
     * The current route group attributes.
     *
     * @var array
     */
    protected static $groupStack = [];

    /**
     * The default namespace for all routes.
     *
     * @var string
     */
    protected static $namespace = 'wordforge/v1';

    /**
     * The globally available parameter patterns.
     *
     * @var array
     */
    protected static $patterns = [];

    /**
     * Initialize the router.
     *
     * @return void
     */
    public static function init()
    {
        self::$routes = new RouteCollection();
    }

    /**
     * Register all routes with WordPress REST API.
     *
     * @return void
     */
    public static function registerRoutes()
    {
        if (!self::$routes) {
            return;
        }

        foreach (self::$routes->getRoutes() as $route) {
            $route->register();
        }
    }

    /**
     * Set a global pattern for a given parameter.
     *
     * @param string $key
     * @param string $pattern
     * @return void
     */
    public static function pattern(string $key, string $pattern)
    {
        self::$patterns[$key] = $pattern;
    }

    /**
     * Set global patterns for parameters.
     *
     * @param array $patterns
     * @return void
     */
    public static function patterns(array $patterns)
    {
        foreach ($patterns as $key => $pattern) {
            self::pattern($key, $pattern);
        }
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param array $attributes
     * @param callable $callback
     * @return void
     */
    public static function group(array $attributes, callable $callback)
    {
        self::updateGroupStack($attributes);

        // Execute the callback, which will register routes in this group
        call_user_func($callback);

        // Remove the last group from the stack after routes are registered
        array_pop(self::$groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param array $attributes
     * @return void
     */
    protected static function updateGroupStack(array $attributes)
    {
        if (!empty(self::$groupStack)) {
            $attributes = self::mergeWithLastGroup($attributes);
        }

        self::$groupStack[] = $attributes;
    }

    public static function addNamedRoute($name, $route)
    {
        if (null === self::$routes) {
            self::init();
        }

        self::$routes->addNamed($name, $route);
    }

    /**
     * Merge the given attributes with the last group stack.
     *
     * @param array $attributes
     * @return array
     */
    protected static function mergeWithLastGroup(array $attributes)
    {
        $lastGroup = end(self::$groupStack);

        // Handle prefix merging
        if (isset($lastGroup['prefix']) && isset($attributes['prefix'])) {
            $attributes['prefix'] = trim($lastGroup['prefix'], '/') . '/' . trim($attributes['prefix'], '/');
        }

        // Handle middleware merging
        if (isset($lastGroup['middleware']) && isset($attributes['middleware'])) {
            $attributes['middleware'] = array_merge(
                (array) $lastGroup['middleware'],
                (array) $attributes['middleware']
            );
        }

        // Handle namespace merging
        if (isset($lastGroup['namespace']) && isset($attributes['namespace'])) {
            if (str_starts_with($attributes['namespace'], '\\')) {
                // If the namespace starts with \, it's absolute
                // Do nothing to merge
            } else {
                // Otherwise, it's relative to the parent namespace
                $attributes['namespace'] = $lastGroup['namespace'] . '\\' . $attributes['namespace'];
            }
        }

        // Handle where conditions merging
        if (isset($lastGroup['where']) && isset($attributes['where'])) {
            $attributes['where'] = array_merge(
                (array) $lastGroup['where'],
                (array) $attributes['where']
            );
        }

        // Add any other attributes from the last group that are not explicitly set in the new attributes
        return array_merge($lastGroup, $attributes);
    }

    /**
     * Add a GET route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function get(string $uri, $action)
    {
        return self::addRoute(['GET'], $uri, $action);
    }

    /**
     * Add a POST route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function post(string $uri, $action)
    {
        return self::addRoute(['POST'], $uri, $action);
    }

    /**
     * Add a PUT route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function put(string $uri, $action)
    {
        return self::addRoute(['PUT'], $uri, $action);
    }

    /**
     * Add a PATCH route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function patch(string $uri, $action)
    {
        return self::addRoute(['PATCH'], $uri, $action);
    }

    /**
     * Add a DELETE route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function delete(string $uri, $action)
    {
        return self::addRoute(['DELETE'], $uri, $action);
    }

    /**
     * Add an OPTIONS route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function options(string $uri, $action)
    {
        return self::addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * Add a route for all available methods.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function any(string $uri, $action)
    {
        return self::addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * Add a route that responds to multiple HTTP methods.
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public static function match(array $methods, string $uri, $action)
    {
        return self::addRoute($methods, $uri, $action);
    }

    /**
     * Register a resource route.
     *
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return void
     */
    public static function resource(string $name, string $controller, array $options = [])
    {
        $base = trim($name, '/');

        // Set up available actions (Laravel naming conventions)
        $resourceActions = [
            'index' => ['get', $base],
            'store' => ['post', $base],
            'show' => ['get', "$base/{id}"],
            'update' => ['put', "$base/{id}"],
            'destroy' => ['delete', "$base/{id}"],
            'create' => ['get', "$base/create"],
            'edit' => ['get', "$base/{id}/edit"]
        ];

        // Handle only/except options
        $actions = array_keys($resourceActions);

        if (isset($options['only'])) {
            $actions = array_intersect($actions, (array) $options['only']);
        }

        if (isset($options['except'])) {
            $actions = array_diff($actions, (array) $options['except']);
        }

        // Create the routes
        foreach ($actions as $action) {
            list($method, $uri) = $resourceActions[$action];

            // Add parameter constraints if specified
            $route = self::$method($uri, "$controller@$action");

            if (isset($options['where']) && is_array($options['where'])) {
                $route->where($options['where']);
            }

            // Add name to the route
            if (!isset($options['as'])) {
                $route->name("$base.$action");
            } else {
                $route->name("{$options['as']}.$action");
            }
        }
    }

    /**
     * Register an API resource route (no create/edit endpoints).
     *
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return void
     */
    public static function apiResource(string $name, string $controller, array $options = [])
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);

        self::resource($name, $controller, $options);
    }

    /**
     * Register multiple API resource routes.
     *
     * @param array $resources
     * @param array $options
     * @return void
     */
    public static function apiResources(array $resources, array $options = [])
    {
        foreach ($resources as $name => $controller) {
            self::apiResource($name, $controller, $options);
        }
    }

    /**
     * Add a route to the collection.
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    protected static function addRoute(array $methods, string $uri, $action)
    {
        if (empty(self::$routes)) {
            self::init();
        }

        // Prepare action and apply group attributes
        $action = self::parseAction($action);
        $attributes = [];

        if (!empty(self::$groupStack)) {
            $lastGroup = end(self::$groupStack);

            if (isset($lastGroup['prefix'])) {
                $uri = trim($lastGroup['prefix'], '/') . '/' . trim($uri, '/');
            }

            if (isset($lastGroup['middleware'])) {
                $attributes['middleware'] = (array) $lastGroup['middleware'];
            }

            // Pass along any parameter patterns from the group
            if (isset($lastGroup['where'])) {
                $attributes['where'] = $lastGroup['where'];
            }
        }

        $namespace = self::$namespace;
        if (!empty(self::$groupStack) && isset(end(self::$groupStack)['namespace'])) {
            $namespace = end(self::$groupStack)['namespace'];
        }

        // Create the route and add it to the collection
        $route = new Route($methods, $uri, $action, $namespace);

        // Apply global parameter patterns
        if (!empty(self::$patterns)) {
            $route->where(self::$patterns);
        }

        // Apply group attributes
        if (!empty($attributes)) {
            $route->setAttributes($attributes);
        }

        self::$routes->add($route);

        // If the route is named, add it to the named routes collection
        if (isset($action['as'])) {
            $route->name($action['as']);
        }

        return $route;
    }

    /**
     * Parse the action into a standard format.
     *
     * @param mixed $action
     * @return array
     */
    protected static function parseAction($action)
    {
        // If the action is a callable, wrap it in an array
        if (is_callable($action) && !is_string($action)) {
            return ['callback' => $action];
        }

        // If the action is a string...
        if (is_string($action)) {
            // Check if it's a Controller@method format
            if (strpos($action, '@') !== false) {
                list($controller, $method) = explode('@', $action, 2);
                return [
                    'controller' => $controller,
                    'method' => $method
                ];
            }

            // Otherwise, treat it as a simple callback to be resolved later
            return ['uses' => $action];
        }

        // Handle [ClassName::class, 'methodName'] format
        if (is_array($action) && count($action) === 2 && isset($action[0]) && isset($action[1]) && is_string($action[0]) && is_string($action[1])) {
            return [
                'controller' => $action[0],
                'method' => $action[1]
            ];
        }

        // If the action is already an array, return it as is
        return is_array($action) ? $action : ['uses' => $action];
    }

    /**
     * Set the default namespace for all routes.
     *
     * @param string $namespace
     * @return void
     */
    public static function setNamespace(string $namespace)
    {
        self::$namespace = $namespace;
    }

    /**
     * Get a route URL by name.
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    public static function url(string $name, array $parameters = [], bool $absolute = true)
    {
        if (!self::$routes) {
            throw new \InvalidArgumentException("Route collection not initialized.");
        }

        $route = self::$routes->getByName($name);

        if (!$route) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        // Get the URI pattern
        $uri = $route->getUri();

        // Replace parameter placeholders
        foreach ($parameters as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
            $uri = str_replace("{{$key}?}", $value, $uri);
        }

        // Remove any remaining optional parameters
        $uri = preg_replace('/\/{[^\/]+\?}/', '', $uri);

        // Add the base URL if the absolute flag is true
        if ($absolute) {
            $uri = rest_url(self::$namespace . '/' . ltrim($uri, '/'));
        } else {
            $uri = '/' . self::$namespace . '/' . ltrim($uri, '/');
        }

        return $uri;
    }

    /**
     * Get all registered routes.
     *
     * @return array
     */
    public static function getRoutes()
    {
        return self::$routes ? self::$routes->getRoutes() : [];
    }

    /**
     * Get a route by name.
     *
     * @param string $name
     * @return Route|null
     */
    public static function getByName(string $name)
    {
        return self::$routes?->getByName($name);
    }

    /**
     * Clear all routes.
     *
     * @return void
     */
    public static function clearRoutes()
    {
        if (self::$routes) {
            self::$routes->clear();
        }
    }
}
