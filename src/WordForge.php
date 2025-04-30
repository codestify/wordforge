<?php

namespace WordForge;

use WordForge\Database\QueryBuilder;
use WordForge\Http\Request;
use WordForge\Http\Response;
use WordForge\Http\Router\Router;
use WordForge\Support\ServiceManager;

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
     * The framework base path.
     *
     * @var string
     */
    protected static $frameworkPath;

    /**
     * The application base path.
     *
     * @var string
     */
    protected static $appPath;

    /**
     * The framework configuration.
     *
     * @var array
     */
    protected static $config = [];

    /**
     * Whether the framework has been bootstrapped.
     *
     * @var bool
     */
    protected static $bootstrapped = false;

    /**
     * Bootstrap the framework.
     *
     * @param  string  $pluginPath  Path to the plugin's root directory
     *
     * @return void
     */
    public static function bootstrap(string $pluginPath)
    {
        // Prevent double bootstrapping
        if (self::$bootstrapped) {
            return;
        }

        // Set application path (plugin root)
        self::$appPath = $pluginPath;

        // Detect framework path - either same as plugin or in vendor directory
        $possibleFrameworkPath = __DIR__ . '/..';
        if (file_exists($possibleFrameworkPath . '/src/WordForge.php')) {
            self::$frameworkPath = $possibleFrameworkPath;
        } else {
            // If we can't find it, assume it's the same as the app path
            self::$frameworkPath = $pluginPath;
        }

        // Load helper functions if not already loaded
        $helpersFile = self::$frameworkPath . '/src/Support/helpers.php';
        if (file_exists($helpersFile) && ! function_exists('wordforge_config')) {
            require_once $helpersFile;
        }

        // Set up facade aliases for global usage if they don't exist
        if (! class_exists('Route')) {
            class_alias('WordForge\Support\Facades\Route', 'Route');
            class_alias('WordForge\Support\Facades\Response', 'Response');
            class_alias('WordForge\Support\Facades\Request', 'Request');
        }

        // Mark as bootstrapped
        self::$bootstrapped = true;

        // Schedule initialization on WordPress hooks
        add_action('plugins_loaded', [self::class, 'initialize'], 5);
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    /**
     * Initialize the framework components.
     *
     * @return void
     */
    public static function initialize()
    {
        if (! self::$bootstrapped) {
            return;
        }

        // Load configuration
        self::loadConfiguration();

        // Register core services
        self::registerCoreServices();

        // Initialize the router
        Router::init();

        // Load the service providers
        self::loadServiceProviders();

        // Register any other hooks needed
        self::registerHooks();
    }

    /**
     * Load the framework configuration.
     *
     * @return void
     */
    protected static function loadConfiguration()
    {
        // First load default framework configs if they exist
        $frameworkConfigPath = self::$frameworkPath . '/config';
        if (is_dir($frameworkConfigPath)) {
            foreach (glob($frameworkConfigPath . '/*.php') as $file) {
                $name                = basename($file, '.php');
                self::$config[$name] = require $file;
            }
        }

        // Then load and merge app configs, giving them priority
        $appConfigPath = self::$appPath . '/config';
        if (is_dir($appConfigPath) && $appConfigPath !== $frameworkConfigPath) {
            foreach (glob($appConfigPath . '/*.php') as $file) {
                $name = basename($file, '.php');

                // If config already exists, merge with app config taking precedence
                if (isset(self::$config[$name]) && is_array(self::$config[$name])) {
                    $defaultConfig       = self::$config[$name];
                    $appConfig           = require $file;
                    self::$config[$name] = self::mergeConfigs($defaultConfig, $appConfig);
                } else {
                    // Otherwise just use the app config
                    self::$config[$name] = require $file;
                }
            }
        }

        // Ensure we have at least a minimal app config if none was found
        if (! isset(self::$config['app'])) {
            self::$config['app'] = [
                'name'       => 'WordForge App',
                'api_prefix' => 'wordforge/v1',
                'providers'  => [],
            ];
        }
    }

    /**
     * Recursively merge configs with app config values taking precedence
     *
     * @param  array  $default  Default config
     * @param  array  $app  App config
     *
     * @return array Merged config
     */
    protected static function mergeConfigs(array $default, array $app)
    {
        $merged = $default;

        foreach ($app as $key => $value) {
            // If value is array and exists in default, merge recursively
            if (is_array($value) && isset($default[$key]) && is_array($default[$key])) {
                $merged[$key] = self::mergeConfigs($default[$key], $value);
            } else {
                // Otherwise app value overrides default
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Register core services
     *
     * @return void
     */
    protected static function registerCoreServices()
    {
        // Register the framework itself
        ServiceManager::instance('wordforge', self::class);

        // Register router service
        ServiceManager::singleton('router', function () {
            return Router::class;
        });

        // Register request service (create a fresh one each time)
        ServiceManager::register('request', function ($wpRequest = null) {
            if ($wpRequest === null) {
                // Try to get the current WP REST Request
                global $wp_rest_server;
                if ($wp_rest_server && property_exists($wp_rest_server, 'current_request')) {
                    $wpRequest = $wp_rest_server->current_request;
                }

                // If still null, create a mock request
                if ($wpRequest === null) {
                    $wpRequest = new \WP_REST_Request();
                }
            }

            return new Request($wpRequest);
        });

        // Register response factory
        ServiceManager::register('response', function ($data = null, $status = 200, $headers = []) {
            return new Response($data, $status, $headers);
        });

        // Register the database query builder
        ServiceManager::register('db', function ($table = null) {
            if ($table === null) {
                return QueryBuilder::class;
            }

            return QueryBuilder::table($table);
        });
    }

    /**
     * Load and register the service providers.
     *
     * @return void
     */
    protected static function loadServiceProviders()
    {
        $providers = self::config('app.providers', []);

        // Use the new service provider manager
        ServiceProviderManager::register($providers);
    }

    /**
     * Get a configuration value.
     *
     * @param  string  $key
     * @param  mixed  $default
     *
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
     * Register the framework's routes with WordPress.
     *
     * @return void
     */
    public static function registerRoutes(): void
    {
        if (! self::$bootstrapped) {
            return;
        }

        // Allow plugins to disable default route loading
        if (apply_filters('wordforge_load_routes', true)) {
            // First try app routes file
            $appRoutesFile = self::$appPath . '/routes/api.php';
            if (file_exists($appRoutesFile)) {
                require_once $appRoutesFile;
            } else {
                // Fall back to framework routes file or config setting
                $routesFile = self::config('app.routes_file', self::$frameworkPath . '/routes/api.php');
                if (file_exists($routesFile)) {
                    require_once $routesFile;
                }
            }

            // Register the routes with WordPress
            Router::registerRoutes();
        }
    }

    /**
     * Get the framework path.
     *
     * @param  string  $path
     *
     * @return string
     */
    public static function frameworkPath(string $path = '')
    {
        return self::$frameworkPath . ($path ? '/' . $path : '');
    }

    /**
     * Get the base path (alias for appPath for backward compatibility)
     *
     * @param  string  $path
     *
     * @return string
     */
    public static function basePath(string $path = '')
    {
        return self::appPath($path);
    }

    /**
     * Get the application path.
     *
     * @param  string  $path
     *
     * @return string
     */
    public static function appPath(string $path = '')
    {
        return self::$appPath . ($path ? '/' . $path : '');
    }

    /**
     * Get the URL to an asset.
     *
     * @param  string  $path
     *
     * @return string
     */
    public static function assetUrl(string $path)
    {
        // Find the main plugin file
        $pluginFile = self::findPluginFile(self::$appPath);

        return plugins_url('assets/' . $path, $pluginFile);
    }

    /**
     * Find the main plugin file in the given directory
     *
     * @param  string  $directory
     *
     * @return string
     */
    protected static function findPluginFile($directory)
    {
        // First look for typical plugin filenames
        $commonNames = ['plugin.php', 'index.php', basename($directory) . '.php'];

        foreach ($commonNames as $name) {
            if (file_exists($directory . '/' . $name)) {
                return $directory . '/' . $name;
            }
        }

        // Otherwise, look for any PHP file with Plugin Name: in the header
        foreach (glob($directory . '/*.php') as $file) {
            $content = file_get_contents($file);
            if (preg_match('/Plugin Name:/i', $content)) {
                return $file;
            }
        }

        // If no plugin file found, return directory
        return $directory;
    }

    /**
     * Get the path to a view file.
     *
     * @param  string  $view
     *
     * @return string
     */
    public static function viewPath(string $view)
    {
        $view = str_replace('.', '/', $view);

        // First check in app views
        $appViewPath = self::$appPath . '/views/' . $view . '.php';
        if (file_exists($appViewPath)) {
            return $appViewPath;
        }

        // Fall back to framework views
        return self::$frameworkPath . '/views/' . $view . '.php';
    }

    /**
     * Generate a URL to a named route.
     *
     * @param  string  $name
     * @param  array  $parameters
     *
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
        if (! empty($parameters)) {
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

    /**
     * Check if the framework has been bootstrapped.
     *
     * @return bool
     */
    public static function isBootstrapped()
    {
        return self::$bootstrapped;
    }
}
