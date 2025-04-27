<?php

namespace WordForge\Http\Router;

use WordForge\Http\Request;

/**
 * Route class for handling WordPress REST API routes with Laravel-style syntax
 *
 * @package WordForge\Http\Router
 */
class Route
{
    /**
     * The HTTP methods this route responds to.
     *
     * @var array
     */
    protected $methods;

    /**
     * The original route URI pattern (Laravel style).
     *
     * @var string
     */
    protected $uri;

    /**
     * The WordPress-compatible route pattern.
     *
     * @var string
     */
    protected $wpPattern;

    /**
     * The route action array.
     *
     * @var array
     */
    protected $action;

    /**
     * The namespace for the route.
     *
     * @var string
     */
    protected $namespace;

    /**
     * The route name.
     *
     * @var string|null
     */
    protected $name;

    /**
     * The route middleware.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Route parameter patterns.
     *
     * @var array
     */
    protected $patterns = [];

    /**
     * Default patterns for route parameters.
     *
     * @var array
     */
    protected $defaultPatterns = [
        '*'    => '([^/]+)', // Default - match anything except slashes
        'id'   => '(\d+)',  // ID - match digits
        'slug' => '([a-z0-9-]+)', // Slug - match alphanumeric and dash
        'uuid' => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})' // UUID pattern
    ];

    /**
     * Create a new Route instance.
     *
     * @param  array  $methods
     * @param  string  $uri
     * @param  array|callable|string  $action
     * @param  string  $namespace
     *
     * @return void
     */
    public function __construct(array $methods, string $uri, $action, string $namespace)
    {
        $this->methods   = $methods;
        $this->uri       = $uri;
        $this->action    = $this->parseAction($action);
        $this->namespace = $namespace;
        $this->wpPattern = $this->convertUriToWordPressPattern($uri);
    }

    /**
     * Parse the action into a standard format.
     *
     * @param  mixed  $action
     *
     * @return array
     */
    protected function parseAction($action)
    {
        if (is_string($action)) {
            if (strpos($action, '@') !== false) {
                list($controller, $method) = explode('@', $action);

                return [
                    'controller' => $controller,
                    'method'     => $method
                ];
            }

            return ['uses' => $action];
        }

        if (is_callable($action)) {
            return ['callback' => $action];
        }

        return $action;
    }

    /**
     * Convert Laravel-style URI to WordPress REST API pattern.
     *
     * @param  string  $uri
     *
     * @return string
     */
    protected function convertUriToWordPressPattern(string $uri)
    {
        // Replace Laravel-style {parameter} with WordPress (?P<parameter>pattern)
        return preg_replace_callback('/{([a-z0-9_]+)(\?)?}/', function ($matches) {
            $paramName  = $matches[1];
            $isOptional = isset($matches[2]) && $matches[2] === '?';

            // Get the pattern for this parameter
            $pattern = $this->getParameterPattern($paramName);

            // For optional parameters, make the whole group optional
            if ($isOptional) {
                // WordPress doesn't directly support optional parameters in routes
                // So we'll have to register multiple routes to handle this
                // For now, we'll mark it and handle it during registration
                return "(?P<$paramName>$pattern)?";
            }

            return "(?P<$paramName>$pattern)";
        }, $uri);
    }

    /**
     * Get the regex pattern for a parameter.
     *
     * @param  string  $paramName
     *
     * @return string
     */
    protected function getParameterPattern(string $paramName)
    {
        // Check if we have a specific pattern for this parameter
        if (isset($this->patterns[$paramName])) {
            return $this->patterns[$paramName];
        }

        // Check if we have a default pattern for this parameter name
        if (isset($this->defaultPatterns[$paramName])) {
            return $this->defaultPatterns[$paramName];
        }

        // Use the default pattern
        return $this->defaultPatterns['*'];
    }

    /**
     * Set a regular expression requirement on the route.
     *
     * @param  string|array  $name
     * @param  string|null  $expression
     *
     * @return $this
     */
    public function where($name, $expression = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->where($key, $value);
            }

            return $this;
        }

        $this->patterns[$name] = $expression;
        // Update the WordPress pattern with the new constraints
        $this->wpPattern = $this->convertUriToWordPressPattern($this->uri);

        return $this;
    }

    /**
     * Set route attributes from a group.
     *
     * @param  array  $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        if (isset($attributes['middleware'])) {
            $this->middleware = array_merge($this->middleware, (array)$attributes['middleware']);
        }

        if (isset($attributes['where'])) {
            $this->where($attributes['where']);
        }

        return $this;
    }

    /**
     * Add middleware to the route.
     *
     * @param  string|array  $middleware
     *
     * @return $this
     */
    public function middleware($middleware)
    {
        $this->middleware = array_merge(
            $this->middleware,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $this;
    }

    /**
     * Set the name of the route.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        Router::addNamedRoute($name, $this);

        return $this;
    }

    /**
     * Get the name of the route.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the URI of the route.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get the WordPress pattern of the route.
     *
     * @return string
     */
    public function getWordPressPattern()
    {
        $parts      = explode('/', $this->wpPattern);
        $paramParts = [];

        foreach ($parts as $part) {
            if (strpos($part, '(?P<') !== false) {
                $paramParts[] = $part;
            }
        }

        return implode('/', $paramParts);
    }

    /**
     * Get the full WordPress pattern including the URI prefix.
     *
     * @return string
     */
    public function getFullWordPressPattern()
    {
        return $this->wpPattern;
    }

    /**
     * Check if the route has any optional parameters.
     *
     * @return bool
     */
    public function hasOptionalParameters()
    {
        return strpos($this->uri, '?}') !== false;
    }

    /**
     * Generate all possible route combinations for optional parameters.
     *
     * @return array
     */
    public function getOptionalRouteCombinations()
    {
        if (! $this->hasOptionalParameters()) {
            return [$this->wpPattern];
        }

        // Extract all parameters
        preg_match_all('/{([a-z0-9_]+)(\?)?}/', $this->uri, $matches, PREG_SET_ORDER);

        // Build a map of optional parameters
        $optionalParams = [];
        foreach ($matches as $match) {
            $paramName  = $match[1];
            $isOptional = isset($match[2]) && $match[2] === '?';

            if ($isOptional) {
                $optionalParams[] = $paramName;
            }
        }

        // Generate combinations
        $combinations = [];
        $total        = pow(2, count($optionalParams));

        for ($i = 0; $i < $total; $i++) {
            $combination = $this->uri;

            for ($j = 0; $j < count($optionalParams); $j++) {
                $param   = $optionalParams[$j];
                $include = ($i >> $j) & 1;

                if (! $include) {
                    // Remove the optional parameter
                    $combination = preg_replace('/{' . $param . '\?}([^{]*)?/', '', $combination);
                } else {
                    // Replace the optional marker
                    $combination = str_replace('{' . $param . '?}', '{' . $param . '}', $combination);
                }
            }

            // Clean up any trailing slashes from removed segments
            $combination = preg_replace('#/+#', '/', $combination);
            $combination = rtrim($combination, '/');

            // Convert to WordPress pattern
            $wpPattern = $this->convertUriToWordPressPattern($combination);

            $combinations[] = $wpPattern;
        }

        return array_unique($combinations);
    }

    /**
     * Register the route with WordPress REST API.
     *
     * @return void
     */
    public function register()
    {
        if ($this->hasOptionalParameters()) {
            // For routes with optional parameters, register multiple routes
            $patterns = $this->getOptionalRouteCombinations();

            foreach ($patterns as $pattern) {
                $this->registerWordPressRoute($pattern);
            }
        } else {
            // For simple routes, just register once
            $this->registerWordPressRoute($this->wpPattern);
        }
    }

    /**
     * Register a WordPress REST API route with the given pattern.
     *
     * @param  string  $pattern
     *
     * @return void
     */
    protected function registerWordPressRoute(string $pattern)
    {
        foreach ($this->methods as $method) {
            register_rest_route($this->namespace, $pattern, [
                'methods'             => $method,
                'callback'            => [$this, 'handleRequest'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args'                => $this->buildArgumentsSchema(),
            ]);
        }
    }

    protected function buildArgumentsSchema()
    {
        $args = [];

        // Extract parameter names from the pattern
        preg_match_all('/\(\?P<([a-z0-9_]+)>/', $this->wpPattern, $matches);

        if (! empty($matches[1])) {
            foreach ($matches[1] as $param) {
                $args[$param] = [
                    'required'          => ! $this->isParameterOptional($param),
                    'validate_callback' => function ($value, $request, $key) use ($param) {
                        try {
                            return $this->validateParameter($param, $value);
                        } catch (\Exception $e) {
                            // Log the error but don't block the request in production
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log('Parameter validation error: ' . $e->getMessage());
                            }

                            return true;
                        }
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ];
            }
        }

        return $args;
    }

    /**
     * Check if a parameter is optional.
     *
     * @param  string  $param
     *
     * @return bool
     */
    protected function isParameterOptional(string $param)
    {
        return strpos($this->uri, '{' . $param . '?}') !== false;
    }

    /**
     * Validate a route parameter against its pattern.
     *
     * @param  string  $param
     * @param  mixed  $value
     *
     * @return bool
     */
    protected function validateParameter(string $param, $value)
    {
        try {
            $pattern = $this->getParameterPattern($param);

            // Check if a custom pattern was explicitly set
            $hasCustomPattern = isset($this->patterns[$param]);

            // If no custom pattern was set, just return true (accept any value)
            if (! $hasCustomPattern) {
                return true;
            }

            // For custom patterns, apply proper validation
            if (strpos($pattern, '(') === 0 && substr($pattern, -1) === ')') {
                // Pattern already has capturing groups, extract the pattern
                $pattern = substr($pattern, 1, -1);
            } else {
                // Pattern without capturing groups
                $pattern = $pattern;
            }

            // Add delimiters and anchors
            $delimitedPattern = '/^' . $pattern . '$/';

            // Perform the validation
            return preg_match($delimitedPattern, $value) === 1;
        } catch (\Exception $e) {
            // Log errors in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Route parameter validation error: ' . $e->getMessage());
            }

            // In case of any error, accept the parameter
            return true;
        }
    }

    /**
     * Handle the incoming request.
     *
     * @param  \WP_REST_Request  $wpRequest
     *
     * @return \WP_REST_Response
     */
    public function handleRequest(\WP_REST_Request $wpRequest)
    {
        // Create our request wrapper
        $request = new Request($wpRequest);

        // Run middleware
        $middlewareResult = $this->runMiddleware($request);
        if ($middlewareResult !== true) {
            return $middlewareResult instanceof \WP_REST_Response
                ? $middlewareResult
                : new \WP_REST_Response(['error' => 'Unauthorized'], 403);
        }

        // Execute the route action
        $response = $this->runAction($request);

        // Convert response to WP_REST_Response if needed
        if ($response instanceof \WordForge\Http\Response) {
            return $response->toWordPress();
        }

        if (! ($response instanceof \WP_REST_Response)) {
            $response = new \WP_REST_Response($response);
        }

        return $response;
    }

    /**
     * Check permissions for the route.
     *
     * @param  \WP_REST_Request  $request
     *
     * @return bool|\WP_Error
     */
    public function checkPermissions(\WP_REST_Request $request)
    {
        // By default, always return true
        // Permissions will be handled by middleware
        return true;
    }

    /**
     * Run the route middleware stack.
     *
     * @param  Request  $request
     *
     * @return bool|\WP_REST_Response
     */
    protected function runMiddleware(Request $request)
    {
        foreach ($this->middleware as $middleware) {
            // Handle FormRequest validation middleware
            if (str_starts_with($middleware, 'validate:')) {
                $formRequestClass = substr($middleware, 9);
                if (class_exists($formRequestClass)) {
                    $formRequest      = new $formRequestClass($request->getWordPressRequest());
                    $validationResult = $formRequest->validate();

                    if ($validationResult !== true) {
                        return new \WP_REST_Response([
                            'message' => 'The given data was invalid.',
                            'errors'  => $validationResult
                        ], 422);
                    }
                }
                continue;
            }

            // Handle regular middleware
            if (class_exists($middleware)) {
                $middlewareInstance = new $middleware();
                $result             = $middlewareInstance->handle($request);

                if ($result !== true) {
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * Run the route action.
     *
     * @param  Request  $request
     *
     * @return mixed
     */
    protected function runAction(Request $request)
    {
        if (isset($this->action['callback']) && is_callable($this->action['callback'])) {
            return call_user_func($this->action['callback'], $request);
        }

        if (isset($this->action['controller'], $this->action['method'])) {
            $controller = $this->action['controller'];
            $method     = $this->action['method'];

            if (class_exists($controller)) {
                $instance = new $controller();

                // Add this block to process controller middleware
                if (method_exists($instance, 'getMiddleware')) {
                    $controllerMiddleware = $instance->getMiddleware();
                    foreach ($controllerMiddleware as $middleware) {
                        $middlewareClass = $middleware['middleware'];
                        if (class_exists($middlewareClass)) {
                            $middlewareInstance = new $middlewareClass();
                            $result             = $middlewareInstance->handle($request);

                            // If middleware returns anything other than true, return that response
                            if ($result !== true) {
                                if ($result instanceof \WordForge\Http\Response) {
                                    return $result->toWordPress();
                                }

                                return $result;
                            }
                        }
                    }
                }

                if (method_exists($instance, $method)) {
                    return $instance->{$method}($request);
                }
            }
        }

        return new \WP_REST_Response([
            'message' => 'Route action not found'
        ], 500);
    }
}
