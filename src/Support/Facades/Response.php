<?php

namespace WordForge\Support\Facades;

/**
 * Response Facade
 *
 * @method static \WordForge\Http\Response json(mixed $data = null, int $status = 200, array $headers = [])
 * @method static \WordForge\Http\Response success(mixed $data = null, int $status = 200, array $headers = [])
 * @method static \WordForge\Http\Response error(string $message, int $status = 400, array $headers = [])
 * @method static \WordForge\Http\Response validationError(array $errors, string $message = 'The given data was invalid', array $headers = [])
 * @method static \WordForge\Http\Response notFound(string $message = 'Resource not found', array $headers = [])
 * @method static \WordForge\Http\Response unauthorized(string $message = 'Unauthorized', array $headers = [])
 * @method static \WordForge\Http\Response forbidden(string $message = 'Forbidden', array $headers = [])
 * @method static \WordForge\Http\Response noContent(array $headers = [])
 * @method static \WordForge\Http\Response created(mixed $data = null, array $headers = [])
 * @method static \WordForge\Http\Response accepted(mixed $data = null, array $headers = [])
 * @method static \WordForge\Http\Response header(string $name, string $value)
 * @method static \WordForge\Http\Response withHeaders(array $headers)
 * @method static \WordForge\Http\Response setStatusCode(int $statusCode)
 * @method static int getStatusCode()
 * @method static mixed getData()
 * @method static \WordForge\Http\Response setData(mixed $data)
 * @method static array getHeaders()
 * @method static \WP_REST_Response toWordPress()
 *
 * @package WordForge\Support\Facades
 */
class Response extends Facade
{
    /**
     * Get the facade accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \WordForge\Http\Response::class;
    }
}
