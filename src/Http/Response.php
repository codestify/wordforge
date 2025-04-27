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
     * The response data.
     *
     * @var mixed
     */
    protected $data;

    /**
     * The response status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * The response headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Create a new Response instance.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return void
     */
    public function __construct($data = null, int $status = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    /**
     * Create a new JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function json($data = null, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';

        return new static($data, $status, $headers);
    }

    /**
     * Create a new successful response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function success($data = null, int $status = 200, array $headers = [])
    {
        return static::json([
            'success' => true,
            'data' => $data
        ], $status, $headers);
    }

    /**
     * Create a new error response.
     *
     * @param string $message
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function error(string $message, int $status = 400, array $headers = [])
    {
        return static::json([
            'success' => false,
            'error' => $message
        ], $status, $headers);
    }

    /**
     * Create a new validation error response.
     *
     * @param array $errors
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function validationError(array $errors, string $message = 'The given data was invalid', array $headers = [])
    {
        return static::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422, $headers);
    }

    /**
     * Create a new "not found" response.
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function notFound(string $message = 'Resource not found', array $headers = [])
    {
        return static::error($message, 404, $headers);
    }

    /**
     * Create a new "unauthorized" response.
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function unauthorized(string $message = 'Unauthorized', array $headers = [])
    {
        return static::error($message, 401, $headers);
    }

    /**
     * Create a new "forbidden" response.
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function forbidden(string $message = 'Forbidden', array $headers = [])
    {
        return static::error($message, 403, $headers);
    }

    /**
     * Create a new "no content" response.
     *
     * @param array $headers
     * @return static
     */
    public static function noContent(array $headers = [])
    {
        return new static(null, 204, $headers);
    }

    /**
     * Create a new "created" response.
     *
     * @param mixed $data
     * @param array $headers
     * @return static
     */
    public static function created($data = null, array $headers = [])
    {
        return static::success($data, 201, $headers);
    }

    /**
     * Create a new "accepted" response.
     *
     * @param mixed $data
     * @param array $headers
     * @return static
     */
    public static function accepted($data = null, array $headers = [])
    {
        return static::success($data, 202, $headers);
    }

    /**
     * Add a header to the response.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function header(string $name, string $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Add multiple headers to the response.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        return $this;
    }

    /**
     * Set the response status code.
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Get the response status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get the response data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the response data.
     *
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the response headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Convert the response to a WordPress REST API response.
     *
     * @return \WP_REST_Response
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
