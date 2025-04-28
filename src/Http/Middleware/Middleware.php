<?php

namespace WordForge\Http\Middleware;

use WordForge\Http\Request;

/**
 * Base Middleware interface
 *
 * Provides the contract for middleware implementation.
 *
 * @package WordForge\Http\Middleware
 */
interface Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     *
     * @return mixed
     */
    public function handle(Request $request);
}
