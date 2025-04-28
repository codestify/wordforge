<?php

namespace Tests\Unit\Http\Router;

use Tests\TestCase;
use WordForge\Http\Router\Route;
use WordForge\Http\Router\Router;

class RouterTest extends TestCase
{
    /**
     * Test the init method initializes the router.
     */
    public function testInit()
    {
        // Act
        Router::init();

        // Assert
        $reflectionClass = new \ReflectionClass(Router::class);
        $routesProperty  = $reflectionClass->getProperty('routes');
        $routesProperty->setAccessible(true);

        $this->assertInstanceOf(\WordForge\Http\Router\RouteCollection::class, $routesProperty->getValue());
    }

    /**
     * Test the registerRoutes method registers all routes.
     */
    public function testRegisterRoutes()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Add a route
        Router::get('posts', 'PostController@index');

        // Act
        Router::registerRoutes();

        // Assert - difficult to test directly, but it should not error
        $this->addToAssertionCount(1);
    }

    /**
     * Test the pattern method sets a global parameter pattern.
     */
    public function testPattern()
    {
        // Act
        Router::pattern('id', '(\d+)');

        // Assert
        $reflectionClass  = new \ReflectionClass(Router::class);
        $patternsProperty = $reflectionClass->getProperty('patterns');
        $patternsProperty->setAccessible(true);

        $patterns = $patternsProperty->getValue();
        $this->assertEquals('(\d+)', $patterns['id']);
    }

    /**
     * Test the patterns method sets multiple global parameter patterns.
     */
    public function testPatterns()
    {
        // Act
        Router::patterns([
            'id'   => '(\d+)',
            'slug' => '([a-z0-9-]+)'
        ]);

        // Assert
        $reflectionClass  = new \ReflectionClass(Router::class);
        $patternsProperty = $reflectionClass->getProperty('patterns');
        $patternsProperty->setAccessible(true);

        $patterns = $patternsProperty->getValue();
        $this->assertEquals('(\d+)', $patterns['id']);
        $this->assertEquals('([a-z0-9-]+)', $patterns['slug']);
    }

    /**
     * Test the group method creates a route group.
     */
    public function testGroup()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        Router::group(['prefix' => 'api'], function () {
            Router::get('posts', 'PostController@index');
        });

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('api/posts', $routes[0]->getUri());
    }

    /**
     * Test nested groups correctly merge attributes.
     */
    public function testNestedGroups()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        Router::group(['prefix' => 'api', 'middleware' => ['api']], function () {
            Router::group(['prefix' => 'v1', 'middleware' => ['throttle']], function () {
                Router::get('posts', 'PostController@index');
            });
        });

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(1, $routes);
        $this->assertEquals('api/v1/posts', $routes[0]->getUri());

        // Check middleware - it's a bit more complex as it's a protected property
        $reflectionClass    = new \ReflectionClass($routes[0]);
        $middlewareProperty = $reflectionClass->getProperty('middleware');
        $middlewareProperty->setAccessible(true);

        $middleware = $middlewareProperty->getValue($routes[0]);
        $this->assertEquals(['api', 'throttle'], $middleware);
    }

    /**
     * Test the HTTP verb methods create the correct routes.
     */
    public function testHttpVerbMethods()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        $getRoute     = Router::get('posts', 'PostController@index');
        $postRoute    = Router::post('posts', 'PostController@store');
        $putRoute     = Router::put('posts/{id}', 'PostController@update');
        $patchRoute   = Router::patch('posts/{id}', 'PostController@patch');
        $deleteRoute  = Router::delete('posts/{id}', 'PostController@destroy');
        $optionsRoute = Router::options('posts', 'PostController@options');
        $anyRoute     = Router::any('any', 'AnyController@handle');
        $matchRoute   = Router::match(['GET', 'POST'], 'match', 'MatchController@handle');

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(8, $routes);

        // Check method arrays
        $reflectionClass = new \ReflectionClass(Route::class);
        $methodsProperty = $reflectionClass->getProperty('methods');
        $methodsProperty->setAccessible(true);

        $this->assertEquals(['GET'], $methodsProperty->getValue($getRoute));
        $this->assertEquals(['POST'], $methodsProperty->getValue($postRoute));
        $this->assertEquals(['PUT'], $methodsProperty->getValue($putRoute));
        $this->assertEquals(['PATCH'], $methodsProperty->getValue($patchRoute));
        $this->assertEquals(['DELETE'], $methodsProperty->getValue($deleteRoute));
        $this->assertEquals(['OPTIONS'], $methodsProperty->getValue($optionsRoute));
        $this->assertEquals(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            $methodsProperty->getValue($anyRoute));
        $this->assertEquals(['GET', 'POST'], $methodsProperty->getValue($matchRoute));
    }

    /**
     * Test the resource method creates resource routes.
     */
    public function testResource()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        Router::resource('posts', 'PostController');

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(7, $routes); // Should create 7 resource routes

        // Check that all necessary route names exist
        $routeNames = [];
        foreach ($routes as $route) {
            $routeNames[] = $route->getName();
        }

        $this->assertContains('posts.index', $routeNames);
        $this->assertContains('posts.create', $routeNames);
        $this->assertContains('posts.store', $routeNames);
        $this->assertContains('posts.show', $routeNames);
        $this->assertContains('posts.edit', $routeNames);
        $this->assertContains('posts.update', $routeNames);
        $this->assertContains('posts.destroy', $routeNames);
    }

    /**
     * Test the resource method with only option.
     */
    public function testResourceWithOnly()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        Router::resource('posts', 'PostController', ['only' => ['index', 'show']]);

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(2, $routes);

        $routeNames = [];
        foreach ($routes as $route) {
            $routeNames[] = $route->getName();
        }

        $this->assertContains('posts.index', $routeNames);
        $this->assertContains('posts.show', $routeNames);
    }

    /**
     * Test the resource method with except option.
     */
    public function testResourceWithExcept()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        Router::resource('posts', 'PostController', ['except' => ['create', 'edit']]);

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(5, $routes);

        $routeNames = [];
        foreach ($routes as $route) {
            $routeNames[] = $route->getName();
        }

        $this->assertContains('posts.index', $routeNames);
        $this->assertContains('posts.store', $routeNames);
        $this->assertContains('posts.show', $routeNames);
        $this->assertContains('posts.update', $routeNames);
        $this->assertContains('posts.destroy', $routeNames);

        $this->assertNotContains('posts.create', $routeNames);
        $this->assertNotContains('posts.edit', $routeNames);
    }

    /**
     * Test the apiResource method creates API resource routes.
     */
    public function testApiResource()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        Router::apiResource('posts', 'PostController');

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(5, $routes); // Excludes create and edit

        $routeNames = [];
        foreach ($routes as $route) {
            $routeNames[] = $route->getName();
        }

        $this->assertContains('posts.index', $routeNames);
        $this->assertContains('posts.store', $routeNames);
        $this->assertContains('posts.show', $routeNames);
        $this->assertContains('posts.update', $routeNames);
        $this->assertContains('posts.destroy', $routeNames);

        $this->assertNotContains('posts.create', $routeNames);
        $this->assertNotContains('posts.edit', $routeNames);
    }

    /**
     * Test the apiResources method creates multiple API resources.
     */
    public function testApiResources()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        Router::apiResources([
            'posts'    => 'PostController',
            'comments' => 'CommentController'
        ]);

        // Assert
        $routes = Router::getRoutes();
        $this->assertCount(10, $routes); // 5 routes x 2 resources

        $routeNames = [];
        foreach ($routes as $route) {
            $routeNames[] = $route->getName();
        }

        $this->assertContains('posts.index', $routeNames);
        $this->assertContains('posts.store', $routeNames);
        $this->assertContains('posts.show', $routeNames);
        $this->assertContains('posts.update', $routeNames);
        $this->assertContains('posts.destroy', $routeNames);

        $this->assertContains('comments.index', $routeNames);
        $this->assertContains('comments.store', $routeNames);
        $this->assertContains('comments.show', $routeNames);
        $this->assertContains('comments.update', $routeNames);
        $this->assertContains('comments.destroy', $routeNames);
    }

    /**
     * Test the parseAction method handles different action formats.
     */
    public function testParseAction()
    {
        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass(Router::class);
        $method          = $reflectionClass->getMethod('parseAction');
        $method->setAccessible(true);

        // Test Controller@method format
        $result = $method->invoke(null, 'PostController@show');
        $this->assertEquals(['controller' => 'PostController', 'method' => 'show'], $result);

        // Test callable/closure format
        $closure = function () {
            return 'test';
        };
        $result  = $method->invoke(null, $closure);
        $this->assertEquals(['callback' => $closure], $result);

        // Test array format
        $result = $method->invoke(null, ['PostController', 'show']);
        $this->assertEquals(['controller' => 'PostController', 'method' => 'show'], $result);

        // Test already formatted array
        $action = ['controller' => 'PostController', 'method' => 'show'];
        $result = $method->invoke(null, $action);
        $this->assertEquals($action, $result);
    }

    /**
     * Test the setNamespace method sets the default namespace.
     */
    public function testSetNamespace()
    {
        // Act
        Router::setNamespace('custom/namespace');

        // Assert
        $reflectionClass   = new \ReflectionClass(Router::class);
        $namespaceProperty = $reflectionClass->getProperty('namespace');
        $namespaceProperty->setAccessible(true);

        $this->assertEquals('custom/namespace', $namespaceProperty->getValue());
    }

    /**
     * Test the url method generates URLs from named routes.
     */
    public function testUrl()
    {
        $this->mockWpFunction('register_rest_route', true);

        // Use a different approach to mock rest_url
        // This depends on how your test framework handles mocks
        // For example, using PHPUnit's built-in functionality:
        global $rest_url_mock;
        $rest_url_mock = function ($path) {
            return 'http://example.com/wp-json/'.$path;
        };

        // Override the function for this test
        function rest_url($path)
        {
            global $rest_url_mock;

            return $rest_url_mock($path);
        }

        // Add a named route
        Router::get('posts/{id}', 'PostController@show')->name('posts.show');

        // Act
        $url = Router::url('posts.show', ['id' => 123]);

        // Assert
        $this->assertEquals('http://example.com/wp-json/wordforge/v1/posts/123', $url);
    }

    /**
     * Test the url method with relative URLs.
     */
    public function testUrlRelative()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Add a named route
        Router::get('posts/{id}', 'PostController@show')->name('posts.show');

        // Act
        $url = Router::url('posts.show', ['id' => 123], false);

        // Assert
        $this->assertEquals('/wordforge/v1/posts/123', $url);
    }

    /**
     * Test the getRoutes method returns all routes.
     */
    public function testGetRoutes()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        Router::get('posts', 'PostController@index');
        Router::post('posts', 'PostController@store');

        // Act
        $routes = Router::getRoutes();

        // Assert
        $this->assertCount(2, $routes);
        $this->assertInstanceOf(Route::class, $routes[0]);
        $this->assertInstanceOf(Route::class, $routes[1]);
    }

    /**
     * Test the getByName method returns a named route.
     */
    public function testGetByName()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        Router::get('posts', 'PostController@index')->name('posts.index');

        // Act
        $route = Router::getByName('posts.index');

        // Assert
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('posts.index', $route->getName());
    }

    /**
     * Test the clearRoutes method resets all routes.
     */
    public function testClearRoutes()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        Router::get('posts', 'PostController@index');
        $this->assertCount(1, Router::getRoutes());

        // Act
        Router::clearRoutes();

        // Assert
        $this->assertCount(0, Router::getRoutes());
    }

    /**
     * Reset the Router's static properties before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the router's static state
        $reflectionClass = new \ReflectionClass(Router::class);

        $routesProperty = $reflectionClass->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue(null, null);

        $groupStackProperty = $reflectionClass->getProperty('groupStack');
        $groupStackProperty->setAccessible(true);
        $groupStackProperty->setValue(null, []);

        $namespaceProperty = $reflectionClass->getProperty('namespace');
        $namespaceProperty->setAccessible(true);
        $namespaceProperty->setValue(null, 'wordforge/v1');

        $patternsProperty = $reflectionClass->getProperty('patterns');
        $patternsProperty->setAccessible(true);
        $patternsProperty->setValue(null, []);

        // Initialize the router
        Router::init();
    }
}