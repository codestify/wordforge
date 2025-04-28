<?php

namespace Tests\Unit\Http\Router;

use Tests\TestCase;
use WordForge\Http\Router\Router;

class DummyController
{
    public function index()
    {
        return 'index method';
    }

    public function show()
    {
        return 'show method';
    }
}

class RouterArraySyntaxTest extends TestCase
{
    /**
     * Test routes with array syntax for controllers.
     */
    public function testRouteWithArraySyntax()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', true);

        // Act
        $route = Router::get('test', [DummyController::class, 'index']);

        // Assert
        $reflectionClass = new \ReflectionClass($route);
        $actionProperty  = $reflectionClass->getProperty('action');
        $actionProperty->setAccessible(true);
        $action = $actionProperty->getValue($route);

        $this->assertEquals(DummyController::class, $action['controller']);
        $this->assertEquals('index', $action['method']);
    }

    /**
     * Test parseAction method correctly handles array syntax.
     */
    public function testParseActionWithArraySyntax()
    {
        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass(Router::class);
        $method          = $reflectionClass->getMethod('parseAction');
        $method->setAccessible(true);

        // Test with class name string
        $result   = $method->invoke(null, [DummyController::class, 'show']);
        $expected = [
            'controller' => DummyController::class,
            'method'     => 'show'
        ];
        $this->assertEquals($expected, $result);

        // Test with object and class name
        $controller = new DummyController();
        $result     = $method->invoke(null, [$controller::class, 'index']);
        $expected   = [
            'controller' => $controller::class,
            'method'     => 'index'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that routes with array syntax can be registered and work properly.
     */
    public function testRouteRegistrationWithArraySyntax()
    {
        // Arrange
        $this->mockWpFunction('register_rest_route', function ($namespace, $route, $args) {
            // Store the callback for later verification
            $this->lastCallback = $args['callback'];

            return true;
        });

        // Act
        Router::get('users', [DummyController::class, 'index']);
        Router::registerRoutes();

        // Assert
        // Verify the action is correctly set up
        $routes = Router::getRoutes();
        $this->assertCount(1, $routes);

        // Check that the route has the correct controller and method
        $reflectionClass = new \ReflectionClass($routes[0]);
        $actionProperty  = $reflectionClass->getProperty('action');
        $actionProperty->setAccessible(true);
        $action = $actionProperty->getValue($routes[0]);

        $this->assertEquals(DummyController::class, $action['controller']);
        $this->assertEquals('index', $action['method']);
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
