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
     *
     * @var array
     */
    protected $routes = [];

    /**
     * The named routes stored in the collection.
     *
     * @var array
     */
    protected $namedRoutes = [];

    /**
     * Add a route to the collection.
     *
     * @param Route $route
     * @return void
     */
    public function add(Route $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Get all routes in the collection.
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get a route by name.
     *
     * @param string $name
     * @return Route|null
     */
    public function getByName(string $name)
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Add a named route to the collection.
     *
     * @param string $name
     * @param Route $route
     * @return void
     */
    public function addNamed(string $name, Route $route)
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * Count the number of routes in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->routes);
    }

    /**
     * Clear the route collection.
     *
     * @return void
     */
    public function clear()
    {
        $this->routes = [];
        $this->namedRoutes = [];
    }
}
