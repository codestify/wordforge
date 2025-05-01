<?php

namespace WordForge\Http\Router;

/**
 * RouteCollection class for storing and retrieving routes
 *
 * @package WordForge\Http\Router
 */
class RouteCollection
{
    /**
     * The routes stored in the collection.
     */
    protected array $routes = [];

    /**
     * The named routes stored in the collection.
     */
    protected array $namedRoutes = [];

    /**
     * Add a route to the collection.
     */
    public function add(Route $route): void
    {
        $this->routes[] = $route;
    }

    /**
     * Get all routes in the collection.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get a route by name.
     */
    public function getByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Add a named route to the collection.
     */
    public function addNamed(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * Count the number of routes in the collection.
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Clear the route collection.
     */
    public function clear(): void
    {
        $this->routes      = [];
        $this->namedRoutes = [];
    }
}
