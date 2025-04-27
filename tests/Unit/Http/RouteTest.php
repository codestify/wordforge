<?php

namespace Tests\Unit\Http\Router;

use Tests\TestCase;
use WordForge\Http\Router\Route;

class RouteTest extends TestCase
{
    /**
     * Test the constructor creates a route instance correctly.
     */
    public function testConstructor()
    {
        // Arrange
        $methods = ['GET', 'POST'];
        $uri = 'posts/{id}';
        $action = 'PostController@show';
        $namespace = 'wordforge/v1';

        // Act
        $route = new Route($methods, $uri, $action, $namespace);

        // Assert
        $this->assertEquals($uri, $route->getUri());
        $this->assertEquals('(?P<id>(\d+))', $route->getWordPressPattern());
    }

    /**
     * Test the where method applies pattern constraints to route parameters.
     */
    public function testWhere()
    {
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');

        $route->where('id', '(\d+)');

        $this->assertEquals('(?P<id>(\d+))', $route->getWordPressPattern());

        // Test with array of where constraints
        $route = new Route(['GET'], 'posts/{category}/{slug}', 'PostController@show', 'wordforge/v1');
        $route->where([
            'category' => '([a-z]+)',
            'slug' => '([a-z0-9-]+)'
        ]);

        $this->assertEquals('(?P<category>([a-z]+))/(?P<slug>([a-z0-9-]+))', $route->getWordPressPattern());

        // Test method chaining
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');
        $this->assertSame($route, $route->where('id', '(\d+)'));
    }

    /**
     * Test the middleware method adds middleware to the route.
     */
    public function testMiddleware()
    {
        // Arrange
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');

        // Act
        $route->middleware('auth');

        // Assert - using reflection to access protected properties
        $reflectionClass = new \ReflectionClass($route);
        $middlewareProperty = $reflectionClass->getProperty('middleware');
        $middlewareProperty->setAccessible(true);

        $this->assertEquals(['auth'], $middlewareProperty->getValue($route));

        // Test adding multiple middleware
        $route->middleware(['throttle', 'cache']);
        $this->assertEquals(['auth', 'throttle', 'cache'], $middlewareProperty->getValue($route));

        // Test method chaining
        $this->assertSame($route, $route->middleware('cors'));
    }

    /**
     * Test the name method sets the route name.
     */
    public function testName()
    {
        // Arrange
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');

        // Act
        $route->name('posts.show');

        // Assert
        $this->assertEquals('posts.show', $route->getName());

        // Test method chaining
        $this->assertSame($route, $route->name('api.posts.show'));
        $this->assertEquals('api.posts.show', $route->getName());
    }

    /**
     * Test the hasOptionalParameters method detects optional parameters.
     */
    public function testHasOptionalParameters()
    {
        // Arrange & Act & Assert
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');
        $this->assertFalse($route->hasOptionalParameters());

        $route = new Route(['GET'], 'posts/{id?}', 'PostController@show', 'wordforge/v1');
        $this->assertTrue($route->hasOptionalParameters());

        $route = new Route(['GET'], 'posts/{category}/{slug?}', 'PostController@show', 'wordforge/v1');
        $this->assertTrue($route->hasOptionalParameters());
    }

    /**
     * Test the getOptionalRouteCombinations method generates all possible route combinations.
     */
    public function testGetOptionalRouteCombinations()
    {
        // Arrange
        $route = new Route(['GET'], 'posts/{category}/{slug?}', 'PostController@show', 'wordforge/v1');

        // Act
        $combinations = $route->getOptionalRouteCombinations();

        // Assert
        $this->assertCount(2, $combinations);
        $this->assertContains('posts/(?P<category>([^/]+))', $combinations);
        $this->assertContains('posts/(?P<category>([^/]+))/(?P<slug>([a-z0-9-]+))', $combinations);

        // Test with multiple optional parameters
        $route = new Route(['GET'], 'posts/{category?}/{year?}/{slug?}', 'PostController@show', 'wordforge/v1');
        $combinations = $route->getOptionalRouteCombinations();

        // Should have 2^3 = 8 combinations
        $this->assertCount(8, $combinations);
    }

    /**
     * Test the handleRequest method processes a request correctly.
     */
    public function testHandleRequest()
    {
        // Arrange
        $route = new Route(['GET'], 'posts/{id}', function($request) {
            return new \WP_REST_Response(['id' => $request->param('id')], 200);
        }, 'wordforge/v1');

        $wpRequest = $this->createMock(\WP_REST_Request::class);
        $wpRequest->method('get_url_params')
                  ->willReturn(['id' => '123']);

        // Act
        $response = $route->handleRequest($wpRequest);

        // Assert
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(['id' => '123'], $response->get_data());
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test the handleRequest method with a controller action.
     */
    public function testHandleRequestWithController()
    {
        // Create a mock controller class
        $mockController = new class {
            public function show($request) {
                return new \WP_REST_Response(['id' => $request->param('id')], 200);
            }
        };

        // Get the class name of the mock controller
        $controllerClass = get_class($mockController);

        // Arrange
        $route = new Route(['GET'], 'posts/{id}', [
            'controller' => $controllerClass,
            'method' => 'show'
        ], 'wordforge/v1');

        $wpRequest = $this->createMock(\WP_REST_Request::class);
        $wpRequest->method('get_url_params')
                  ->willReturn(['id' => '123']);

        // Act
        $response = $route->handleRequest($wpRequest);

        // Assert
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(['id' => '123'], $response->get_data());
        $this->assertEquals(200, $response->get_status());
    }

    /**
     * Test the register method registers the route with WordPress.
     */
    public function testRegister()
    {
        // This is tricky to test since it calls WordPress functions
        // But we can test that it doesn't throw exceptions

        $this->mockWpFunction('register_rest_route', true);

        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');

        // Act & Assert - should not throw exception
        $route->register();
        $this->addToAssertionCount(1); // Counts this as a successful assertion

        // Test with optional parameters
        $route = new Route(['GET'], 'posts/{category?}/{slug?}', 'PostController@show', 'wordforge/v1');
        $route->register();
        $this->addToAssertionCount(1);
    }

    /**
     * Test the checkPermissions method.
     */
    public function testCheckPermissions()
    {
        // By default, it should always return true
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');

        $wpRequest = $this->createMock(\WP_REST_Request::class);

        // Act & Assert
        $this->assertTrue($route->checkPermissions($wpRequest));
    }

    /**
     * Test the runMiddleware method with middleware that passes.
     */
    public function testRunMiddlewareSuccess()
    {
        // Create a mock middleware class
        $mockMiddleware = new class {
            public function handle($request) {
                return true;
            }
        };

        // Get the class name of the mock middleware
        $middlewareClass = get_class($mockMiddleware);

        // Arrange
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');
        $route->middleware($middlewareClass);

        $wpRequest = $this->createMock(\WP_REST_Request::class);

        // Use reflection to access the protected runMiddleware method
        $reflectionClass = new \ReflectionClass($route);
        $runMiddlewareMethod = $reflectionClass->getMethod('runMiddleware');
        $runMiddlewareMethod->setAccessible(true);

        // Act
        $result = $runMiddlewareMethod->invoke($route, new \WordForge\Http\Request($wpRequest));

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test the runMiddleware method with middleware that fails.
     */
    public function testRunMiddlewareFailure()
    {
        // Create a mock middleware class that fails
        $mockMiddleware = new class {
            public function handle($request) {
                return new \WP_REST_Response(['error' => 'Unauthorized'], 403);
            }
        };

        // Get the class name of the mock middleware
        $middlewareClass = get_class($mockMiddleware);

        // Arrange
        $route = new Route(['GET'], 'posts/{id}', 'PostController@show', 'wordforge/v1');
        $route->middleware($middlewareClass);

        $wpRequest = $this->createMock(\WP_REST_Request::class);

        // Use reflection to access the protected runMiddleware method
        $reflectionClass = new \ReflectionClass($route);
        $runMiddlewareMethod = $reflectionClass->getMethod('runMiddleware');
        $runMiddlewareMethod->setAccessible(true);

        // Act
        $result = $runMiddlewareMethod->invoke($route, new \WordForge\Http\Request($wpRequest));

        // Assert
        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $this->assertEquals(403, $result->get_status());
    }

    /**
     * Test converting Laravel-style URI to WordPress pattern.
     */
    public function testConvertUriToWordPressPattern()
    {
        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass(Route::class);
        $method = $reflectionClass->getMethod('convertUriToWordPressPattern');
        $method->setAccessible(true);

        $route = new Route(['GET'], 'dummy', 'dummy', 'dummy');

        // Test simple parameter with ID - update expectation to match default pattern
        $this->assertEquals(
            '(?P<id>(\d+))',
            $method->invoke($route, '{id}')
        );

        // Test with pattern constraint (this should remain the same)
        $route->where('id', '(\d+)');
        $this->assertEquals(
            '(?P<id>(\d+))',
            $method->invoke($route, '{id}')
        );

        // Test optional parameter - update to match default ID pattern
        $this->assertEquals(
            '(?P<id>(\d+))?',
            $method->invoke($route, '{id?}')
        );

        // Test multiple parameters - update expectations for slug pattern
        $this->assertEquals(
            '(?P<category>([^/]+))/(?P<slug>([a-z0-9-]+))',
            $method->invoke($route, '{category}/{slug}')
        );
    }
}