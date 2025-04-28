<?php

namespace WordForge\Http\Controllers;

use WordForge\Http\Request;
use WordForge\Http\Response;

/**
 * Base Controller class for WordPress REST API controllers
 *
 * @package WordForge\Http\Controllers
 */
abstract class Controller
{
    /**
     * The middleware assigned to the controller.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Register middleware on the controller.
     *
     * @param  string|array  $middleware
     * @param  array  $options
     *
     * @return $this
     */
    public function middleware($middleware, array $options = [])
    {
        foreach ((array)$middleware as $m) {
            $this->middleware[] = [
                'middleware' => $m,
                'options'    => $options,
            ];
        }

        return $this;
    }

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return \WordForge\Http\Response
     */
    public function callAction($method, $parameters)
    {
        return call_user_func_array([$this, $method], $parameters);
    }

    /**
     * Create a new response instance.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function response($data = null, int $status = 200, array $headers = [])
    {
        return new Response($data, $status, $headers);
    }

    /**
     * Create a new JSON response.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function json($data = null, int $status = 200, array $headers = [])
    {
        return Response::json($data, $status, $headers);
    }

    /**
     * Create a new success response.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function success($data = null, int $status = 200, array $headers = [])
    {
        return Response::success($data, $status, $headers);
    }

    /**
     * Create a new error response.
     *
     * @param  string  $message
     * @param  int  $status
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function error(string $message, int $status = 400, array $headers = [])
    {
        return Response::error($message, $status, $headers);
    }

    /**
     * Create a new "not found" response.
     *
     * @param  string  $message
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function notFound(string $message = 'Resource not found', array $headers = [])
    {
        return Response::notFound($message, $headers);
    }

    /**
     * Create a new "forbidden" response.
     *
     * @param  string  $message
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function forbidden(string $message = 'Forbidden', array $headers = [])
    {
        return Response::forbidden($message, $headers);
    }

    /**
     * Create a new "no content" response.
     *
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function noContent(array $headers = [])
    {
        return Response::noContent($headers);
    }

    /**
     * Create a new "created" response.
     *
     * @param  mixed  $data
     * @param  array  $headers
     *
     * @return \WordForge\Http\Response
     */
    public function created($data = null, array $headers = [])
    {
        return Response::created($data, $headers);
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param  Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     *
     * @return array|bool
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = new \WordForge\Validation\Validator(
            $request->all(),
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            return $validator->errors();
        }

        return true;
    }
}
