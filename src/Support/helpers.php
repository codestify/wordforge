<?php

/**
 * WordForge helpers
 *
 * Laravel-inspired helper functions for WordPress.
 */

if (! function_exists('wordforge_request')) {
    /**
     * Get the current request instance or a specific input item.
     *
     * @param  string|null  $key
     * @param  mixed|null  $default
     *
     * @return mixed|\WordForge\Http\Request
     */
    function wordforge_request(string $key = null, mixed $default = null): mixed
    {
        $request = \WordForge\Support\Facades\Request::getFacadeInstance();

        if (is_null($key)) {
            return $request;
        }

        return $request->input($key, $default);
    }
}

if (! function_exists('wordforge_response')) {
    /**
     * Create a new response instance.
     *
     * @param  mixed|null  $data
     * @param  int  $status
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    function wordforge_response(mixed $data = null, int $status = 200, array $headers = []): \WordForge\Http\Response
    {
        return new \WordForge\Http\Response($data, $status, $headers);
    }
}

if (! function_exists('wordforge_json')) {
    /**
     * Create a new JSON response.
     *
     * @param  mixed|null  $data
     * @param  int  $status
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    function wordforge_json(mixed $data = null, int $status = 200, array $headers = []): \WordForge\Http\Response
    {
        return \WordForge\Support\Facades\Response::json($data, $status, $headers);
    }
}

if (! function_exists('wordforge_redirect')) {
    /**
     * Create a redirect response.
     *
     * @param  string  $url
     * @param  int  $status
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    function wordforge_redirect($url, $status = 302, array $headers = []): \WordForge\Http\Response
    {
        $response = new \WordForge\Http\Response(null, $status, $headers);
        $response->header('Location', $url);

        return $response;
    }
}

if (! function_exists('wordforge_view')) {
    /**
     * Render a view template.
     *
     * @param  string  $view
     * @param  array  $data
     *
     * @return string
     */
    function wordforge_view($view, $data = []): string
    {
        // Extract data to make variables available to the view
        if (is_array($data) && ! empty($data)) {
            extract($data);
        }

        // Get the view file path
        $viewPath = \WordForge\WordForge::viewPath($view);

        // Start output buffering
        ob_start();

        // Include the view file
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "View not found: $view";
        }

        // Return the buffered content
        return ob_get_clean();
    }
}

if (! function_exists('wordforge_config')) {
    /**
     * Get a configuration value.
     *
     * @param  string  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    function wordforge_config($key, $default = null)
    {
        return \WordForge\WordForge::config($key, $default);
    }
}

if (! function_exists('wordforge_url')) {
    /**
     * Generate a URL to a named route.
     *
     * @param  string  $name
     * @param  array  $parameters
     *
     * @return string
     */
    function wordforge_url($name, $parameters = [])
    {
        // Implementation would depend on the router's functionality
        // This is a placeholder that assumes route names are registered
        return \WordForge\WordForge::url($name, $parameters);
    }
}

if (! function_exists('wordforge_asset')) {
    /**
     * Generate a URL to an asset.
     *
     * @param  string  $path
     *
     * @return string
     */
    function wordforge_asset($path)
    {
        return \WordForge\WordForge::assetUrl($path);
    }
}

if (! function_exists('wordforge_csrf_field')) {
    /**
     * Generate a CSRF token form field.
     *
     * @return string
     */
    function wordforge_csrf_field()
    {
        $token = wp_create_nonce('wordforge_csrf');

        return '<input type="hidden" name="_token" value="' . esc_attr($token) . '">';
    }
}

if (! function_exists('wordforge_method_field')) {
    /**
     * Generate a form field for spoofing the HTTP verb.
     *
     * @param  string  $method
     *
     * @return string
     */
    function wordforge_method_field($method)
    {
        return '<input type="hidden" name="_method" value="' . esc_attr($method) . '">';
    }
}

if (! function_exists('wordforge_old')) {
    /**
     * Get an old input value from the session.
     *
     * @param  string  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    function wordforge_old($key, $default = null)
    {
        // WordPress doesn't have built-in session management,
        // so this is a simple implementation using transients
        $transient = get_transient('wordforge_old_input');
        $data      = $transient ? $transient : [];

        return $data[$key] ?? $default;
    }
}

if (! function_exists('wordforge_session')) {
    /**
     * Get or set a session value.
     *
     * @param  string  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    function wordforge_session($key = null, $value = null, $default = null)
    {
        // Simple session implementation using WordPress transients
        $session = get_transient('wordforge_session') ?: [];

        // If no arguments, return all session data
        if (is_null($key)) {
            return $session;
        }

        // If value is provided, set the session value
        if (! is_null($value)) {
            $session[$key] = $value;
            set_transient('wordforge_session', $session, HOUR_IN_SECONDS);

            return $value;
        }

        // Otherwise, get the session value
        return $session[$key] ?? $default;
    }
}

if (! function_exists('wordforge_auth')) {
    /**
     * Get the authenticated user.
     *
     * @return \WP_User|false
     */
    function wordforge_auth()
    {
        return wp_get_current_user();
    }
}

if (! function_exists('wordforge_collect')) {
    /**
     * Create a new collection instance.
     *
     * @param  mixed  $items
     *
     * @return \WordForge\Support\Collection
     */
    function wordforge_collect($items = [])
    {
        return new \WordForge\Support\Collection($items);
    }
}

if (! function_exists('wordforge_dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed  ...$args
     *
     * @return void
     */
    function wordforge_dd(...$args)
    {
        foreach ($args as $arg) {
            echo '<pre>';
            var_dump($arg);
            echo '</pre>';
        }

        die(1);
    }
}

if (! function_exists('wordforge_service')) {
    /**
     * Get a service from the service manager
     * 
     * @param string|null $name
     * @param mixed ...$params
     * @return mixed
     */
    function wordforge_service($name = null, ...$params)
    {
        if ($name === null) {
            return \WordForge\Support\ServiceManager::class;
        }
        
        return \WordForge\Support\ServiceManager::get($name, ...$params);
    }
}

if (! function_exists('wordforge_has_service')) {
    /**
     * Check if a service exists
     * 
     * @param string $name
     * @return bool
     */
    function wordforge_has_service($name)
    {
        return \WordForge\Support\ServiceManager::has($name);
    }
}
