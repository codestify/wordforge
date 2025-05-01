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
     */
    protected static array $services = [];

    /**
     * Resolved instances
     */
    protected static array $instances = [];

    /**
     * Get a service
     *
     * @param  array  $params
     *
     * @return mixed
     * @throws \Exception
     */
    public static function get(string $name, ...$params): mixed
    {
        if (! self::has($name)) {
            throw new \Exception("Service '$name' not registered");
        }

        return call_user_func_array(self::$services[$name], $params);
    }

    /**
     * Check if a service exists
     */
    public static function has(string $name): bool
    {
        return isset(self::$services[$name]);
    }

    /**
     * Set an instance directly
     */
    public static function instance(string $name, mixed $instance): void
    {
        self::$instances[$name] = $instance;

        self::singleton($name, function () use ($instance) {
            return $instance;
        });
    }

    /**
     * Register a singleton service
     */
    public static function singleton(string $name, callable $factory): void
    {
        self::register($name, function (...$args) use ($factory, $name) {
            if (! isset(self::$instances[$name])) {
                self::$instances[$name] = $factory(...$args);
            }

            return self::$instances[$name];
        });
    }

    /**
     * Register a service factory
     */
    public static function register(string $name, callable $factory): void
    {
        self::$services[$name] = $factory;
    }

    /**
     * Clear all services (mainly for testing)
     */
    public static function clear(): void
    {
        self::$services  = [];
        self::$instances = [];
    }
}
