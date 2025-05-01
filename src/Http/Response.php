<?php

namespace WordForge\Http;

/**
 * Response class for handling WordPress REST API responses
 *
 * Provides a Laravel-like interface for creating responses.
 *
 * @package WordForge\Http
 */
class Response
{
    /**
     * Create a new Response instance.
     */
    public function __construct(
        /**
         * The response data.
         */
        protected mixed $data = null,
        /**
         * The response status code.
         */
        protected int $statusCode = 200,
        /**
         * The response headers.
         */
        protected array $headers = []
    ) {
    }

    /**
     * Create a new validation error response.
     */
    public static function validationError(
        array $errors,
        string $message = 'The given data was invalid',
        array $headers = []
    ): static {
        return static::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ], 422, $headers);
    }

    /**
     * Create a new JSON response.
     */
    public static function json(mixed $data = null, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';

        return new static($data, $status, $headers);
    }

    /**
     * Create a new "not found" response.
     */
    public static function notFound(string $message = 'Resource not found', array $headers = [])
    {
        return static::error($message, 404, $headers);
    }

    /**
     * Create a new error response.
     */
    public static function error(string $message, int $status = 400, array $headers = [])
    {
        return static::json([
            'success' => false,
            'error'   => $message
        ], $status, $headers);
    }

    /**
     * Create a new "unauthorized" response.
     */
    public static function unauthorized(string $message = 'Unauthorized', array $headers = [])
    {
        return static::error($message, 401, $headers);
    }

    /**
     * Create a new "forbidden" response.
     */
    public static function forbidden(string $message = 'Forbidden', array $headers = [])
    {
        return static::error($message, 403, $headers);
    }

    /**
     * Create a new "no content" response.
     */
    public static function noContent(array $headers = [])
    {
        return new static(null, 204, $headers);
    }

    /**
     * Create a new "created" response.
     */
    public static function created(mixed $data = null, array $headers = [])
    {
        return static::success($data, 201, $headers);
    }

    /**
     * Create a new successful response.
     */
    public static function success(mixed $data = null, int $status = 200, array $headers = [])
    {
        return static::json([
            'success' => true,
            'data'    => $data
        ], $status, $headers);
    }

    /**
     * Create a new "accepted" response.
     */
    public static function accepted(mixed $data = null, array $headers = [])
    {
        return static::success($data, 202, $headers);
    }

    /**
     * Add multiple headers to the response.
     */
    public function withHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        return $this;
    }

    /**
     * Add a header to the response.
     */
    public function header(string $name, string $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Get the response status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set the response status code.
     */
    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Get the response data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the response data.
     */
    public function setData(mixed $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the response headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Convert the response to a WordPress REST API response.
     */
    public function toWordPress()
    {
        $response = new \WP_REST_Response($this->data, $this->statusCode);

        foreach ($this->headers as $name => $value) {
            $response->header($name, $value);
        }

        return $response;
    }
}
