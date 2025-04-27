<?php

namespace Tests\Unit\Support\Facades;

use Tests\TestCase;
use WordForge\Support\Facades\Facade;

class FacadeTest extends TestCase
{
    /**
     * Create a concrete implementation of the abstract Facade.
     */
    private function createFacade()
    {
        // Create a test service class
        $testService = new class {
            public function doSomething()
            {
                return 'done';
            }

            public function withParams($param1, $param2)
            {
                return $param1 . '-' . $param2;
            }
        };

        // Create a test facade class
        return new class($testService) extends Facade {
            private static $testService;

            public function __construct($service)
            {
                self::$testService = $service;
            }

            protected static function getFacadeAccessor()
            {
                return 'test.service';
            }

            // Override to return our test service
            protected static function createFacadeInstance(string $accessor)
            {
                return self::$testService;
            }

            // Expose protected methods for testing
            public static function callGetFacadeInstance()
            {
                return static::getFacadeInstance();
            }

            public static function resetResolvedInstances()
            {
                $reflectionClass = new \ReflectionClass(Facade::class);
                $property = $reflectionClass->getProperty('resolvedInstances');
                $property->setAccessible(true);
                $property->setValue([]);

                return true;
            }
        };
    }

    /**
     * Test the getFacadeAccessor method must be implemented.
     */
    public function testGetFacadeAccessorMustBeImplemented()
    {
        // Create an anonymous class that extends Facade
        $facade = new class extends Facade {
            protected static function getFacadeAccessor()
            {
                // TODO: Implement getFacadeAccessor() method.
            }
        };

        // Expect an error because getFacadeAccessor is not implemented
        $this->expectException(\Error::class);

        // Call a static method to trigger __callStatic
        $facade::someMethod();
    }

    /**
     * Test the getFacadeInstance method returns the correct instance.
     */
    public function testGetFacadeInstance()
    {
        // Arrange
        $facade = $this->createFacade();
        $instance = $facade::callGetFacadeInstance();

        // Act - call it again to get from cache
        $cachedInstance = $facade::callGetFacadeInstance();

        // Assert
        $this->assertSame($instance, $cachedInstance);
    }

    /**
     * Test the __callStatic method correctly forwards calls to the instance.
     */
    public function testCallStatic()
    {
        // Arrange
        $facade = $this->createFacade();

        // Act
        $result = $facade::doSomething();

        // Assert
        $this->assertEquals('done', $result);

        // Test with parameters
        $result = $facade::withParams('hello', 'world');
        $this->assertEquals('hello-world', $result);
    }

    /**
     * Test that resolved instances are cached.
     */
    public function testResolvedInstancesAreCached()
    {
        // Arrange
        $facade = $this->createFacade();
        $facade::resetResolvedInstances();

        // Get reflection access to the protected static property
        $reflectionClass = new \ReflectionClass(Facade::class);
        $property = $reflectionClass->getProperty('resolvedInstances');
        $property->setAccessible(true);

        // Act - Initial state should be empty
        $initialInstances = $property->getValue();

        // Trigger instance resolution
        $facade::doSomething();

        // Get the updated instances
        $updatedInstances = $property->getValue();

        // Assert
        $this->assertEmpty($initialInstances);
        $this->assertNotEmpty($updatedInstances);
        $this->assertArrayHasKey('test.service', $updatedInstances);

        // Reset for other tests
        $facade::resetResolvedInstances();
    }

    /**
     * Test facade with non-existent method.
     */
    public function testNonExistentMethod()
    {
        // Arrange
        $facade = $this->createFacade();

        // Expect an error when calling a non-existent method
        $this->expectException(\Error::class);

        // Act & Assert
        $facade::nonExistentMethod();
    }
}