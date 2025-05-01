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
     */
    protected function mockWpFunction(string $function, mixed $return = null, array $args = []): void
    {
        wp_mock_function($function, $return);
    }

    /**
     * Assert that two SQL queries are equivalent
     */
    protected function assertSqlEquals(string $expected, string $actual, string $message = ''): void
    {
        assert_sql_equals($expected, $actual, $message);
    }
}