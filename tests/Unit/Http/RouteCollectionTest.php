<?php

namespace Tests\Unit\Http\Router;

use Tests\TestCase;
use WordForge\Http\Router\Route;
use WordForge\Http\Router\RouteCollection;

class RouteCollectionTest extends TestCase
{
    /**
     * @var RouteCollection
     */
    protected $collection;

    /**
     * Test the add method adds a route to the collection.
     */
    public function testAdd()
    {
        // Arrange
        $route = $this->createMock(Route::class);

        // Act
        $this->collection->add($route);

        // Assert
        $routes = $this->collection->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertSame($route, $routes[0]);
    }

    /**
     * Test the getRoutes method returns all routes.
     */
    public function testGetRoutes()
    {
        // Arrange
        $route1 = $this->createMock(Route::class);
        $route2 = $this->createMock(Route::class);

        // Act
        $this->collection->add($route1);
        $this->collection->add($route2);

        $routes = $this->collection->getRoutes();

        // Assert
        $this->assertCount(2, $routes);
        $this->assertSame($route1, $routes[0]);
        $this->assertSame($route2, $routes[1]);
    }

    /**
     * Test the getByName method returns a route by name.
     */
    public function testGetByName()
    {
        // Arrange
        $route = $this->createMock(Route::class);

        // Act
        $this->collection->addNamed('posts.index', $route);

        // Assert
        $this->assertSame($route, $this->collection->getByName('posts.index'));
        $this->assertNull($this->collection->getByName('non-existent'));
    }

    /**
     * Test the addNamed method adds a named route.
     */
    public function testAddNamed()
    {
        // Arrange
        $route1 = $this->createMock(Route::class);
        $route2 = $this->createMock(Route::class);

        // Act
        $this->collection->addNamed('posts.index', $route1);
        $this->collection->addNamed('posts.show', $route2);

        // Assert
        $this->assertSame($route1, $this->collection->getByName('posts.index'));
        $this->assertSame($route2, $this->collection->getByName('posts.show'));
    }

    /**
     * Test the count method returns the correct count.
     */
    public function testCount()
    {
        // Arrange & Act & Assert
        $this->assertEquals(0, $this->collection->count());

        // Add some routes
        $route1 = $this->createMock(Route::class);
        $route2 = $this->createMock(Route::class);

        $this->collection->add($route1);
        $this->assertEquals(1, $this->collection->count());

        $this->collection->add($route2);
        $this->assertEquals(2, $this->collection->count());
    }

    /**
     * Test the clear method resets the collection.
     */
    public function testClear()
    {
        // Arrange
        $route1 = $this->createMock(Route::class);
        $route2 = $this->createMock(Route::class);

        $this->collection->add($route1);
        $this->collection->add($route2);
        $this->collection->addNamed('posts.index', $route1);

        // Act
        $this->collection->clear();

        // Assert
        $this->assertEquals(0, $this->collection->count());
        $this->assertEmpty($this->collection->getRoutes());
        $this->assertNull($this->collection->getByName('posts.index'));
    }

    /**
     * Test that named routes are distinct from regular routes.
     */
    public function testNamedRoutesAreDistinct()
    {
        // Arrange
        $route1 = $this->createMock(Route::class);
        $route2 = $this->createMock(Route::class);

        // Act - Only add named routes, not regular routes
        $this->collection->addNamed('posts.index', $route1);
        $this->collection->addNamed('posts.show', $route2);

        // Assert
        $this->assertEquals(0, $this->collection->count());
        $this->assertEmpty($this->collection->getRoutes());

        // Named routes should still be accessible
        $this->assertSame($route1, $this->collection->getByName('posts.index'));
        $this->assertSame($route2, $this->collection->getByName('posts.show'));
    }

    /**
     * Test adding multiple routes with the same name.
     */
    public function testAddingMultipleRoutesWithSameName()
    {
        // Arrange
        $route1 = $this->createMock(Route::class);
        $route2 = $this->createMock(Route::class);

        // Act
        $this->collection->addNamed('posts.index', $route1);
        $this->collection->addNamed('posts.index', $route2);

        // Assert - The second route should override the first
        $this->assertSame($route2, $this->collection->getByName('posts.index'));
    }

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->collection = new RouteCollection();
    }
}