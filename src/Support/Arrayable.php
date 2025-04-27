<?php

namespace WordForge\Support;

/**
 * Interface for objects that can be converted to arrays.
 *
 * @package WordForge\Support
 */
interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray();
}
