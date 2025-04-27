<?php

namespace WordForge\Support\Facades;

/**
 * Request Facade
 *
 * @method static array all()
 * @method static mixed input(string $key, mixed $default = null)
 * @method static array only(array $keys)
 * @method static array except(array $keys)
 * @method static bool has($key)
 * @method static mixed header(string $key, mixed $default = null)
 * @method static array headers()
 * @method static mixed param(string $key, mixed $default = null)
 * @method static array params()
 * @method static string method()
 * @method static bool ajax()
 * @method static bool secure()
 * @method static string uri()
 * @method static string url()
 * @method static string getContent()
 * @method static \WordForge\Http\Request setAttribute(string $key, mixed $value)
 * @method static mixed getAttribute(string $key, mixed $default = null)
 * @method static array getAttributes()
 * @method static bool userCan(string $capability)
 * @method static \WP_User|null user()
 * @method static bool isAuthenticated()
 *
 * @package WordForge\Support\Facades
 */
class Request extends Facade
{
    /**
     * Get the facade accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \WordForge\Http\Request::class;
    }

    /**
     * Create a facade instance.
     *
     * @param string $accessor
     * @return object
     */
    protected static function createFacadeInstance(string $accessor)
    {
        global $wp_rest_server;

        if (!$wp_rest_server) {
            $wp_rest_server = new \WP_REST_Server();
        }

        $wpRequest = new \WP_REST_Request();

        return new \WordForge\Http\Request($wpRequest);
    }
}
