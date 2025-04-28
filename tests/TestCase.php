<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base TestCase for WordForge tests
 *
 * This class provides the foundation for all WordForge tests,
 * working with our existing mocks and helper functions.
 */
class TestCase extends BaseTestCase
{
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset any existing mocks for a clean test
        global $wp_mock_functions;
        $wp_mock_functions = [];
    }

    /**
     * Mock a WordPress function
     *
     * @param  string  $function  Function name
     * @param  mixed  $return  Return value
     * @param  array  $args  Expected arguments
     *
     * @return void
     */
    protected function mockWpFunction(string $function, $return = null, array $args = [])
    {
        wp_mock_function($function, $return);
    }

    /**
     * Assert that two SQL queries are equivalent
     *
     * @param  string  $expected  Expected SQL query
     * @param  string  $actual  Actual SQL query
     * @param  string  $message  Optional assertion message
     *
     * @return void
     */
    protected function assertSqlEquals(string $expected, string $actual, string $message = '')
    {
        assert_sql_equals($expected, $actual, $message);
    }
}