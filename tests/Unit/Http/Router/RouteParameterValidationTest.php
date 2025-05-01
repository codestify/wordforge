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

    /**
     * Test that patterns with forward slashes are handled correctly
     */
    public function testValidateParameterWithSlashes()
    {
        // Create a route
        $route = new Route(['GET'], 'path/{param}', 'TestController@show', 'wordforge/v1');

        // Set a pattern that contains forward slashes
        $route->where('param', 'foo/bar');

        $reflectionClass = new \ReflectionClass($route);
        $method = $reflectionClass->getMethod('validateParameter');
        $method->setAccessible(true);

        // This should match because we're now using # as the delimiter
        $result = $method->invoke($route, 'param', 'foo/bar');
        $this->assertTrue($result);
    }

    /**
     * Test default validation behavior when no pattern is provided
     */
    public function testValidateParameterWithoutPattern()
    {
        // Create a route
        $route = new Route(['GET'], 'test/{param}', 'TestController@show', 'wordforge/v1');

        // Don't set any pattern constraint

        $reflectionClass = new \ReflectionClass($route);
        $method = $reflectionClass->getMethod('validateParameter');
        $method->setAccessible(true);

        // Without a pattern, all values should be accepted
        $result = $method->invoke($route, 'param', 'anything');
        $this->assertTrue($result);

        $result = $method->invoke($route, 'param', '12345');
        $this->assertTrue($result);

        $result = $method->invoke($route, 'param', '');
        $this->assertTrue($result);
    }

    /**
     * Test detailed validation with common regex patterns
     */
    public function testValidateParameterWithCommonPatterns()
    {
        // Create a route
        $route = new Route(['GET'], 'test/{param}', 'TestController@show', 'wordforge/v1');

        // Test with a variety of regex patterns that should now work correctly
        $validPatterns = [
            '[0-9]+' => [
                'valid' => '12345',
                'invalid' => 'abc'
            ],
            '[a-z]+' => [
                'valid' => 'test',
                'invalid' => '123'
            ],
            '[a-zA-Z0-9_-]+' => [
                'valid' => 'Test-123_abc',
                'invalid' => 'Test@123'
            ],
            '(yes|no)' => [
                'valid' => 'yes',
                'invalid' => 'maybe'
            ],
            '\d+' => [
                'valid' => '12345',
                'invalid' => 'abc'
            ],
            '[^0-9]+' => [
                'valid' => 'abc',
                'invalid' => '123'
            ],
            '.+' => [
                'valid' => 'anything',
                'invalid' => ''
            ],
        ];

        $reflectionClass = new \ReflectionClass($route);
        $method = $reflectionClass->getMethod('validateParameter');
        $method->setAccessible(true);

        foreach ($validPatterns as $pattern => $tests) {
            $route->where('param', $pattern);

            // Test valid value
            $result = $method->invoke($route, 'param', $tests['valid']);
            $this->assertTrue($result, "Pattern '$pattern' should match '{$tests['valid']}'");

            // Test invalid value
            $resultInvalid = $method->invoke($route, 'param', $tests['invalid']);
            $this->assertFalse($resultInvalid, "Pattern '$pattern' should not match '{$tests['invalid']}'");
        }
    }

    public function testPostIdRoutePattern()
    {
        // Create a route using the exact pattern from the issue
        $route = new Route(['GET'], '/post/{postId}', 'ContentAnalysisController@getByPost', 'wordforge/v1');

        // Add the numerical constraint
        $route->where('postId', '[0-9]+');

        // Use reflection to access the protected validateParameter method
        $reflectionClass = new \ReflectionClass($route);
        $method = $reflectionClass->getMethod('validateParameter');
        $method->setAccessible(true);

        // Test with a valid post ID (numeric)
        $result = $method->invoke($route, 'postId', '12345');
        $this->assertTrue($result, "The postId pattern should accept numeric values");

        // Test with an invalid post ID (non-numeric)
        $resultInvalid = $method->invoke($route, 'postId', 'abc');
        $this->assertFalse($resultInvalid, "The postId pattern should reject non-numeric values");

        // Test with a numeric ID containing non-digit characters
        $resultMixed = $method->invoke($route, 'postId', '123abc');
        $this->assertFalse($resultMixed, "The postId pattern should reject mixed alphanumeric values");
    }

    public function testPostIdRouteRegistration()
    {
        // This tests that we can register and handle the route without regex errors

        // Mock the WordPress REST API registration function
        $this->mockWpFunction('register_rest_route', true);

        // Create a route with the same pattern
        $route = new Route(['GET'], '/post/{postId}', function ($request) {
            return ['post_id' => $request->param('postId')];
        }, 'wordforge/v1');

        // Add the constraint that was causing issues
        $route->where('postId', '[0-9]+');

        // This should not throw any exceptions
        $route->register();

        // Create a mock request with a valid post ID
        $wpRequest = $this->createMock(\WP_REST_Request::class);
        $wpRequest->method('get_url_params')->willReturn(['postId' => '12345']);

        // Handle the request - should work without errors
        $response = $route->handleRequest($wpRequest);

        // Verify the response
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(['post_id' => '12345'], $response->get_data());
    }
}
