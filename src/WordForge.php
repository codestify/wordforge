<?php

namespace WordForge;

use WordForge\Http\Router\Router;
use WordForge\Support\ServiceProvider;

/**
 * Main WordForge Framework Class
 *
 * @package WordForge
 */
class WordForge
{
    /**
     * The framework version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * The base path of the framework.
     *
     * @var string
     */
    protected static $basePath;

    /**
     * The registered service providers.
     *
     * @var array
     */
    protected static $serviceProviders = [];

    /**
     * The framework configuration.
     *
     * @var array
     */
    protected static $config = [];

    /**
     * Bootstrap the framework.
     *
     * @param string $basePath
     * @return void
     */
    public static function bootstrap(string $basePath)
    {
        self::$basePath = $basePath;

        // Initialize the framework
        self::initialize();

        // Register REST API hooks
        add_action('rest_api_init', [self::class, 'registerRoutes']);

        // Register any other hooks needed
        self::registerHooks();
    }

    /**
     * Initialize the framework.
     *
     * @return void
     */
    protected static function initialize()
    {
        // Load configuration
        self::loadConfiguration();

        // Initialize the router
        Router::init();

        // Load the service providers
        self::loadServiceProviders();
    }

    /**
     * Load the framework configuration.
     *
     * @return void
     */
    protected static function loadConfiguration()
    {
        $configPath = self::$basePath . '/config';

        if (is_dir($configPath)) {
            foreach (glob($configPath . '/*.php') as $file) {
                $name = basename($file, '.php');
                self::$config[$name] = require $file;
            }
        }
    }

    /**
     * Load and register the service providers.
     *
     * @return void
     */
    protected static function loadServiceProviders()
    {
        $providers = self::config('app.providers', []);

        foreach ($providers as $provider) {
            self::registerServiceProvider($provider);
        }
    }

    /**
     * Register a service provider.
     *
     * @param string $provider
     * @return void
     */
    public static function registerServiceProvider(string $provider)
    {
        if (isset(self::$serviceProviders[$provider])) {
            return;
        }

        if (class_exists($provider)) {
            $instance = new $provider();

            if ($instance instanceof ServiceProvider) {
                // Register the provider
                $instance->register();

                // Bootstrap the provider
                $instance->boot();

                self::$serviceProviders[$provider] = $instance;
            }
        }
    }

    /**
     * Register the framework's routes with WordPress.
     *
     * @return void
     */
    public static function registerRoutes(): void
    {
        // Load the routes file if it exists
        $routesFile = self::config('app.routes_file', self::$basePath . '/routes/api.php');
        if (file_exists($routesFile)) {
            require_once $routesFile;
        }

        // Register the routes with WordPress
        Router::registerRoutes();
    }

    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    protected static function registerHooks()
    {
        // The core framework doesn't register any hooks
        // Plugins built on top of this framework will register their own hooks
    }

    /**
     * Get a configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function config(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = self::$config;

        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Get the base path of the framework.
     *
     * @param string $path
     * @return string
     */
    public static function basePath(string $path = '')
    {
        return self::$basePath . ($path ? '/' . $path : '');
    }

    /**
     * Get the URL to an asset.
     *
     * @param string $path
     * @return string
     */
    public static function assetUrl(string $path)
    {
        return plugins_url('assets/' . $path, self::$basePath . '/wordforge.php');
    }

    /**
     * Get the path to a view file.
     *
     * @param string $view
     * @return string
     */
    public static function viewPath(string $view)
    {
        $view = str_replace('.', '/', $view);
        return self::$basePath . '/views/' . $view . '.php';
    }

    /**
     * Generate a URL to a named route.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public static function url(string $name, array $parameters = [])
    {
        // This is a placeholder implementation
        // In a real implementation, it would use the router to generate URLs
        $url = rest_url(self::config('app.api_prefix', 'wordforge/v1'));

        // Append the route name
        $url .= '/' . $name;

        // Add parameters as query string
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }

    /**
     * Get the framework version.
     *
     * @return string
     */
    public static function version()
    {
        return self::VERSION;
    }
}
