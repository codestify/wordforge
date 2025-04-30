<?php

namespace WordForge\Support;

class ParameterConverter
{
    /**
     * Default patterns for route parameters.
     *
     * @var array
     */
    protected $defaultPatterns = [
        '*'           => '([^/]+)',   // Default - match anything except slashes
        'id'          => '(\d+)',     // ID - match digits
        'postId'      => '(\d+)',     // Post ID - match digits (camelCase)
        'post_id'     => '(\d+)',     // Post ID - match digits (snake_case)
        'userId'      => '(\d+)',     // User ID - match digits (camelCase)
        'user_id'     => '(\d+)',     // User ID - match digits (snake_case)
        'commentId'   => '(\d+)',     // Comment ID - match digits (camelCase)
        'comment_id'  => '(\d+)',     // Comment ID - match digits (snake_case)
        'termId'      => '(\d+)',     // Term ID - match digits (camelCase)
        'term_id'     => '(\d+)',     // Term ID - match digits (snake_case)
        'categoryId'  => '(\d+)',     // Category ID - match digits (camelCase)
        'category_id' => '(\d+)',     // Category ID - match digits (snake_case)
        'tagId'       => '(\d+)',     // Tag ID - match digits (camelCase)
        'tag_id'      => '(\d+)',     // Tag ID - match digits (snake_case)
        'slug'        => '([a-z0-9-]+)',  // Slug - match alphanumeric and dash
        'name'        => '([a-z0-9-]+)',  // Name - match alphanumeric and dash
        'uuid'        => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})', // UUID pattern
        'year'        => '(\d{4})',       // Year - 4 digits
        'month'       => '(\d{1,2})',     // Month - 1-2 digits
        'day'         => '(\d{1,2})',     // Day - 1-2 digits
        'page'        => '(\d+)',         // Page number - digits
        'per_page'    => '(\d+)',         // Items per page - digits (WordPress standard)
        'perPage'     => '(\d+)',         // Items per page - digits (camelCase alternative)
        'limit'       => '(\d+)',         // Limit - digits
        'offset'      => '(\d+)',         // Offset - digits
        'status'      => '([a-zA-Z0-9_-]+)', // Status - alphanumeric with underscore/dash
        'type'        => '([a-zA-Z0-9_-]+)', // Type - alphanumeric with underscore/dash
        'format'      => '([a-zA-Z0-9_-]+)', // Format - alphanumeric with underscore/dash
        'group'       => '([a-zA-Z0-9_-]+)',  // Group - alphanumeric with underscore/dash
        'post_type'   => '([a-zA-Z0-9_]+)',   // Post type name - letters, numbers, underscores
        'taxonomy'    => '([a-zA-Z0-9_]+)',   // Taxonomy name - letters, numbers, underscores
        'term'        => '([a-z0-9-]+)',      // Term slug - alphanumeric with hyphens
    ];

    /**
     * Custom patterns defined for specific parameters
     *
     * @var array
     */
    protected $customPatterns = [];

    /**
     * Set a custom pattern for a parameter
     *
     * @param string $param Parameter name
     * @param string $pattern Regex pattern
     * @return $this
     */
    public function setPattern(string $param, string $pattern): self
    {
        $this->customPatterns[$param] = $pattern;
        return $this;
    }

    /**
     * Set multiple custom patterns at once
     *
     * @param array $patterns Key-value pairs of parameter names and patterns
     * @return $this
     */
    public function setPatterns(array $patterns): self
    {
        foreach ($patterns as $param => $pattern) {
            $this->setPattern($param, $pattern);
        }
        return $this;
    }

    /**
     * Convert camelCase to snake_case
     *
     * @param string $input CamelCase input
     * @return string snake_case output
     */
    public function camelToSnake(string $input): string
    {
        // Don't convert if it's already using underscores
        if (str_contains($input, '_')) {
            return $input;
        }

        // Special handling for "Id" suffix
        if (str_ends_with($input, 'Id')) {
            $base = substr($input, 0, -2);
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $base)) . '_id';
        }

        // Normal camelCase to snake_case conversion
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * Convert snake_case to camelCase
     *
     * @param string $input snake_case input
     * @return string camelCase output
     */
    public function snakeToCamel(string $input): string
    {
        // Don't convert if it doesn't contain underscores
        if (! str_contains($input, '_')) {
            return $input;
        }

        // Special handling for '_id' suffix
        if (str_ends_with($input, '_id')) {
            $base = substr($input, 0, -3);
            return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $base)))) . 'Id';
        }

        // Normal snake_case to camelCase conversion
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    /**
     * Convert a Laravel-style URI to WordPress REST API pattern.
     *
     * @param string $uri Laravel-style URI
     * @return array Contains 'pattern' (WP pattern) and 'parameters' (info about params)
     */
    public function convertUri(string $uri): array
    {
        $parameters = [];

        // Process each parameter in the URI and replace it with WordPress pattern
        $pattern = preg_replace_callback(
            '/{\s*([a-z0-9_]+)(?::([^}]+))?(\?)?\s*}/i',
            function ($matches) use (&$parameters) {
                $paramName = $matches[1];
                $constraint = $matches[2] ?? null;
                $isOptional = isset($matches[3]) && $matches[3] === '?';

                // Convert camelCase to snake_case for WordPress compatibility
                $wpParamName = $this->camelToSnake($paramName);

                // Determine the pattern: use inline constraint if available, otherwise use default/custom
                $regexPattern = $constraint ?? $this->getPattern($paramName);

                // Build the WordPress parameter string
                $wpParam = "(?P<$wpParamName>$regexPattern)";
                if ($isOptional) {
                    $wpParam .= "?";
                }

                // Store parameter info
                $parameters[$wpParamName] = [
                    'wp_name' => $wpParamName,
                    'original_name' => $paramName,
                    'optional' => $isOptional,
                    'type' => $this->determineType($paramName),
                    'description' => $this->generateDescription($paramName),
                    'pattern' => $regexPattern,
                ];

                return $wpParam;
            },
            $uri
        );

        // Normalize slashes and remove trailing slash
        $pattern = preg_replace('#/+#', '/', $pattern);
        $pattern = rtrim($pattern, '/');

        return [
            'pattern' => $pattern,
            'parameters' => $parameters
        ];
    }

    /**
     * Extract all parameters from a WordPress-compatible route pattern
     *
     * @param string $pattern WordPress pattern
     * @return array Parameter names
     */
    public function extractParamsFromPattern(string $pattern): array
    {
        $params = [];
        if (preg_match_all('/\(\?P<([a-z0-9_]+)>/', $pattern, $matches)) {
            return $matches[1];
        }
        return $params;
    }

    /**
     * Extract all parameters from a Laravel-style route
     *
     * @param string $uri Laravel route URI
     * @return array Parameter names and optional status
     */
    public function extractParamsFromUri(string $uri): array
    {
        $params = [];
        if (preg_match_all('/{([a-z0-9_]+)(\?)?}/i', $uri, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $param = $match[1];
                $isOptional = isset($match[2]) && $match[2] === '?';
                $params[$param] = [
                    'name' => $param,
                    'optional' => $isOptional
                ];
            }
        }
        return $params;
    }

    /**
     * Get the pattern for a parameter
     *
     * @param string $paramName Parameter name
     * @return string Pattern with capturing group
     */
    public function getPattern(string $paramName): string
    {
        // Check custom patterns first
        if (isset($this->customPatterns[$paramName])) {
            return $this->customPatterns[$paramName];
        }

        // Check alternate format in custom patterns
        $snakeParam = $this->camelToSnake($paramName);
        if ($snakeParam !== $paramName && isset($this->customPatterns[$snakeParam])) {
            return $this->customPatterns[$snakeParam];
        }

        $camelParam = $this->snakeToCamel($paramName);
        if ($camelParam !== $paramName && isset($this->customPatterns[$camelParam])) {
            return $this->customPatterns[$camelParam];
        }

        // Check default patterns
        if (isset($this->defaultPatterns[$paramName])) {
            return $this->defaultPatterns[$paramName];
        }

        // Check converted format in default patterns
        if ($snakeParam !== $paramName && isset($this->defaultPatterns[$snakeParam])) {
            return $this->defaultPatterns[$snakeParam];
        }

        if ($camelParam !== $paramName && isset($this->defaultPatterns[$camelParam])) {
            return $this->defaultPatterns[$camelParam];
        }

        // Use smart detection based on parameter name
        if ($paramName === 'id' || str_ends_with($paramName, 'Id') || str_ends_with($paramName, '_id')) {
            return $this->defaultPatterns['id'];
        }

        if ($paramName === 'slug' || str_ends_with($paramName, 'Slug')) {
            return $this->defaultPatterns['slug'];
        }

        if ($paramName === 'uuid' || str_ends_with($paramName, 'Uuid')) {
            return $this->defaultPatterns['uuid'];
        }

        if (str_contains($paramName, 'date') || str_ends_with($paramName, 'Date')) {
            return '([0-9]{4}-[0-9]{2}-[0-9]{2})'; // YYYY-MM-DD
        }

        // Default pattern for anything else
        return $this->defaultPatterns['*'];
    }

    /**
     * Determine parameter type based on name
     *
     * @param string $paramName Parameter name
     * @return string WordPress parameter type
     */
    public function determineType(string $paramName): string
    {
        if ($paramName === 'id' || str_ends_with($paramName, 'Id') || str_ends_with($paramName, '_id')) {
            return 'integer';
        }

        if (in_array($paramName, ['page', 'per_page', 'limit', 'offset'])) {
            return 'integer';
        }

        return 'string';
    }

    /**
     * Generate parameter description based on name
     *
     * @param string $paramName Parameter name
     * @return string Description
     */
    public function generateDescription(string $paramName): string
    {
        if ($paramName === 'id' || str_ends_with($paramName, 'Id') || str_ends_with($paramName, '_id')) {
            return 'The ID to retrieve';
        }

        if ($paramName === 'slug' || str_ends_with($paramName, 'Slug')) {
            return 'The slug to retrieve';
        }

        if ($paramName === 'uuid') {
            return 'UUID identifier';
        }

        if ($paramName === 'page') {
            return 'Current page of the collection';
        }

        if ($paramName === 'per_page' || $paramName === 'perPage') {
            return 'Maximum number of items to return';
        }

        if ($paramName === 'search') {
            return 'Search term to filter results';
        }

        if ($paramName === 'type' || str_ends_with($paramName, 'Type')) {
            return 'Type filter for the collection';
        }

        if ($paramName === 'status') {
            return 'Status filter for the collection';
        }

        return '';
    }

    /**
     * Build WordPress-compatible route arguments from parameters
     *
     * @param array $parameters Parameter information
     * @return array WordPress REST route args
     */
    public function buildRouteArgs(array $parameters): array
    {
        $args = [];

        foreach ($parameters as $param => $info) {
            $wpName = $info['wp_name'] ?? $param;

            // Add argument for WordPress parameter name
            $args[$wpName] = [
                'required' => !($info['optional'] ?? false),
                'type' => $info['type'] ?? $this->determineType($param),
                'description' => $info['description'] ?? $this->generateDescription($param),
                'sanitize_callback' => 'sanitize_text_field',
            ];

            // Add validate_callback for numeric parameters
            if (($info['type'] ?? $this->determineType($param)) === 'integer') {
                $args[$wpName]['validate_callback'] = function ($value) {
                    return is_numeric($value);
                };
            }
        }

        return $args;
    }

    /**
     * Process URL parameters for consistent access in controllers
     *
     * @param array $params URL parameters from WP_REST_Request
     * @return array Processed parameters
     */
    public function processUrlParameters(array $params): array
    {
        $processed = [];

        foreach ($params as $key => $value) {
            // Process this parameter
            $processed[$key] = $this->formatParameterValue($key, $value);

            // Add alternate format for the key if needed
            $altKey = null;
            if (str_contains($key, '_')) {
                // If key is snake_case, add camelCase too
                $altKey = $this->snakeToCamel($key);
            } elseif (preg_match('/[A-Z]/', $key)) {
                // If key is camelCase, add snake_case too
                $altKey = $this->camelToSnake($key);
            }

            // Add the alternate format if it's different
            if ($altKey && $altKey !== $key) {
                $processed[$altKey] = $processed[$key];
            }
        }

        return $processed;
    }

    /**
     * Format a parameter value based on its name
     *
     * @param string $key Parameter name
     * @param mixed $value Parameter value
     * @return mixed Formatted value
     */
    public function formatParameterValue(string $key, $value)
    {
        // Convert numeric ID parameters to integers
        if (
            is_numeric($value) && (
                $key === 'id' ||
                str_ends_with($key, 'Id') ||
                str_ends_with($key, '_id') ||
                in_array($key, ['page', 'per_page', 'limit', 'offset'])
            )
        ) {
            return (int)$value;
        }

        return $value;
    }
}
