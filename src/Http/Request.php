<?php

namespace WordForge\Http;

use WordForge\Support\ParameterConverter;
use WordForge\Validation\ValidationException;
use WordForge\Validation\Validator;

/**
 * Request class for handling WordPress REST API requests
 *
 * Provides a Laravel-like wrapper around the WP_REST_Request.
 *
 * @package WordForge\Http
 */
class Request
{
    /**
     * The underlying WordPress request.
     */
    protected $wpRequest;

    /**
     * The request attributes.
     */
    protected $attributes = [];

    /**
     * Cached request data.
     */
    protected $cachedData = null;

    /**
     * Parameter converter instance.
     */
    protected ParameterConverter $parameterConverter;

    /**
     * Create a new Request instance.
     */
    public function __construct($wpRequest)
    {
        $this->wpRequest = $wpRequest;
        $this->parameterConverter = new ParameterConverter();
    }

    /**
     * Get the WordPress request instance.
     */
    public function getWordPressRequest()
    {
        return $this->wpRequest;
    }

    /**
     * Get a specific input value from the request.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = null)
    {
        $data = $this->all();

        // If no key specified, return all data
        if ($key === null) {
            return $data;
        }

        // Handle dot notation (e.g., "user.name")
        if (str_contains($key, '.')) {
            return $this->getDotNotationValue($data, $key, $default);
        }

        return $data[$key] ?? $default;
    }

    /**
     * Get all input data from the request.
     */
    public function all()
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        // Get URL parameters (processed through parameter converter)
        $urlParams = $this->params();

        // Get query parameters
        $queryParams = $this->wpRequest->get_query_params() ?: [];

        // Get form/POST parameters
        $postParams = $this->wpRequest->get_body_params() ?: [];

        // Get file parameters
        $fileParams = $this->wpRequest->get_file_params() ?: [];

        // Get JSON body, safely
        $jsonParams = $this->safelyGetJsonParams();

        // Merge all parameters with a defined priority
        // JSON has highest priority, then URL params, then POST, then query string
        $this->cachedData = array_merge(
            $queryParams,   // Lowest priority
            $postParams,
            $urlParams,
            $jsonParams,    // Highest priority
            $fileParams     // Files are separate and shouldn't be overridden
        );

        return $this->cachedData;
    }

    /**
     * Safely get JSON parameters from the request.
     */
    protected function safelyGetJsonParams()
    {
        // First try the built-in get_json_params method
        $jsonParams = $this->wpRequest->get_json_params();

        if (is_array($jsonParams)) {
            return $jsonParams;
        }

        // If it's not an array, try to manually parse the body
        $body = $this->wpRequest->get_body();

        if (empty($body) || ! is_string($body)) {
            return [];
        }

        // Try to decode as JSON
        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // If the body isn't valid JSON, check if it's a string representation of JSON
        // This handles double-encoded JSON
        if (str_starts_with($body, '{') || str_starts_with($body, '[')) {
            // Try removing quotes if it looks like a quoted JSON string
            if (preg_match('/^"(.*)"$/s', $body, $matches)) {
                $unwrapped = stripcslashes($matches[1]);
                $decoded   = json_decode($unwrapped, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    /**
     * Get a value using dot notation.
     *
     * @param  mixed  $default
     *
     * @return mixed
     */
    protected function getDotNotationValue(array $array, string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value    = $array;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Check if the request has a given input item.
     *
     * @param  string|array  $key
     */
    public function has(string|array $key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $data = $this->all();

        foreach ($keys as $value) {
            // Handle dot notation
            if (str_contains($value, '.')) {
                $exists = $this->getDotNotationValue($data, $value, '__NOT_EXISTS__') !== '__NOT_EXISTS__';
                if (! $exists) {
                    return false;
                }
                continue;
            }

            if (! array_key_exists($value, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get multiple input values from the request.
     */
    public function only(array $keys)
    {
        $results = [];
        $data    = $this->all();

        foreach ($keys as $key) {
            // Handle dot notation
            if (str_contains($key, '.')) {
                $value = $this->getDotNotationValue($data, $key, null);
                if ($value !== null) {
                    $this->arraySet($results, $key, $value);
                }
                continue;
            }

            if (array_key_exists($key, $data)) {
                $results[$key] = $data[$key];
            }
        }

        return $results;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param  array  $array
     * @param  mixed  $value
     *
     * @return array
     */
    protected function arraySet(array &$array, ?string $key, mixed $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys        = explode('.', $key);
        $lastSegment = array_pop($keys);
        $current     = &$array;

        foreach ($keys as $segment) {
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current[$lastSegment] = $value;

        return $array;
    }

    /**
     * Get all input except for a specified array of items.
     */
    public function except(array $keys)
    {
        $results = $this->all();

        foreach ($keys as $key) {
            // Handle dot notation
            if (str_contains($key, '.')) {
                $this->arrayUnset($results, $key);
                continue;
            }

            unset($results[$key]);
        }

        return $results;
    }

    /**
     * Unset an array item using "dot" notation.
     */
    protected function arrayUnset(array &$array, string $key)
    {
        $keys        = explode('.', $key);
        $lastSegment = array_pop($keys);
        $current     = &$array;

        foreach ($keys as $segment) {
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                return;
            }
            $current = &$current[$segment];
        }

        unset($current[$lastSegment]);
    }

    /**
     * Get all headers from the request.
     */
    public function headers(): array
    {
        return $this->wpRequest->get_headers();
    }

    /**
     * Get a route parameter.
     *
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed
    {
        $params = $this->params();

        // Check for both camelCase and snake_case versions of the parameter
        if (isset($params[$key])) {
            return $params[$key];
        }

        // Check for alternate format
        $altKey = null;
        if (str_contains($key, '_')) {
            // If key is snake_case, try camelCase
            $altKey = $this->parameterConverter->snakeToCamel($key);
        } else {
            // If key might be camelCase, try snake_case
            $altKey = $this->parameterConverter->camelToSnake($key);
        }

        if ($altKey !== $key && isset($params[$altKey])) {
            return $params[$altKey];
        }

        return $default;
    }

    /**
     * Get all route parameters.
     */
    public function params()
    {
        $params = $this->wpRequest->get_url_params() ?: [];

        // Process parameters using the converter for consistent access
        return $this->parameterConverter->processUrlParameters($params);
    }

    /**
     * Get the request method.
     */
    public function method()
    {
        return $this->wpRequest->get_method();
    }

    /**
     * Determine if the request is the result of an AJAX call.
     */
    public function ajax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    public function url()
    {
        $scheme = $this->secure() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $this->uri();
    }

    /**
     * Determine if the request is over HTTPS.
     */
    public function secure()
    {
        return is_ssl();
    }

    /**
     * Get the request URI.
     */
    public function uri()
    {
        return $this->wpRequest->get_route();
    }

    /**
     * Get the request body content.
     */
    public function getContent()
    {
        return $this->wpRequest->get_body();
    }

    /**
     * Set a request attribute.
     *
     * @param  mixed  $value
     */
    public function setAttribute(string $key, mixed $value)
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get a request attribute.
     *
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get all request attributes.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Convert the request instance to an array.
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Check if the authenticated user has a given capability.
     */
    public function userCan(string $capability)
    {
        return current_user_can($capability);
    }

    /**
     * Get the current authenticated user.
     */
    public function user()
    {
        return wp_get_current_user();
    }

    /**
     * Determine if the user is authenticated.
     */
    public function isAuthenticated(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Get the IP address of the client.
     */
    public function ip(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Determine if the current request is asking for JSON.
     */
    public function wantsJson()
    {
        $acceptable = $this->header('Accept', '');

        return $this->isJson() || str_contains($acceptable, '/json') ||
               str_contains($acceptable, '+json');
    }

    /**
     * Get a header from the request.
     *
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function header(string $key, mixed $default = null)
    {
        $headers = $this->wpRequest->get_headers();
        $key     = strtolower($key);

        return $headers[$key][0] ?? $default;
    }

    /**
     * Determine if the request is a JSON request.
     */
    public function isJson()
    {
        $contentType = $this->header('Content-Type', '');

        return str_contains($contentType, '/json') ||
               str_contains($contentType, '+json');
    }

    /**
     * Get the validated data from the request.
     *
     * @throws ValidationException
     */
    public function validated(array $rules, array $messages = [], array $customAttributes = [])
    {
        $this->validate($rules, $messages, $customAttributes);

        $results = [];
        $data    = $this->all();

        foreach ($rules as $key => $rule) {
            // Handle dot notation keys
            if (str_contains($key, '.')) {
                $value = $this->getDotNotationValue($data, $key, null);
                if ($value !== null) {
                    $this->arraySet($results, $key, $value);
                }
                continue;
            }

            if (array_key_exists($key, $data)) {
                $results[$key] = $data[$key];
            }
        }

        return $results;
    }

    /**
     * Validate the given request with the given validation rules.
     *
     * @throws ValidationException
     */
    public function validate(array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = new Validator(
            $this->all(),
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }
}
