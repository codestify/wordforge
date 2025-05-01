<?php

namespace Tests\Unit\Http\Router;

use Tests\TestCase;
use WordForge\Http\Router\Route;

class RouteParameterValidationTest extends TestCase
{
    /**
     * Test that validates a parameter with a pattern that would cause a regex error
     */
    public function testValidateParameterWithProblematicPattern()
    {
        // Create a route
        $route = new Route(['GET'], 'posts/{post_id}', 'PostController@show', 'wordforge/v1');

        // Set a problematic pattern (pattern containing characters that need escaping)
        $route->where('post_id', '[0-9]+');

        // Use reflection to access the protected validateParameter method
        $reflectionClass = new \ReflectionClass($route);
        $method = $reflectionClass->getMethod('validateParameter');
        $method->setAccessible(true);

        // This should not throw a preg_match error
        $result = $method->invoke($route, 'post_id', '12345');

        // The method should return true for a valid parameter value
        $this->assertTrue($result);

        // Also test against an invalid value
        $resultInvalid = $method->invoke($route, 'post_id', 'abc');
        $this->assertFalse($resultInvalid);
    }

    /**
     * Test to verify that the regex error is fixed for other special characters too
     */
    public function testValidateParameterWithSpecialCharacters()
    {
        // Create a route
        $route = new Route(['GET'], 'test/{param}', 'TestController@show', 'wordforge/v1');

        // Test with various special characters that could cause regex issues
        $problematicPatterns = [
            '[0-9]+',            // Square brackets
            '(test|demo)',       // Parentheses
            'a+b*c?',            // Quantifiers
            'x{1,3}',            // Curly braces
            'a|b',               // Alternation
            '^start',            // Start anchor
            'end$',              // End anchor
            'w.rd',              // Dot
            '\\d+',              // Backslash
            '$[a-z]^'            // Multiple special chars
        ];

        $reflectionClass = new \ReflectionClass($route);
        $method = $reflectionClass->getMethod('validateParameter');
        $method->setAccessible(true);

        foreach ($problematicPatterns as $pattern) {
            $route->where('param', $pattern);

            try {
                // Just verify that it doesn't throw an exception
                $method->invoke($route, 'param', 'test123');
                $this->addToAssertionCount(1); // Count as success if no exception
            } catch (\Exception $e) {
                $this->fail('Regex pattern "' . $pattern . '" caused an exception: ' . $e->getMessage());
            }
        }
    }
}
