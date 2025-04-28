<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use WordForge\Support\ServiceManager;

class ServiceManagerTest extends TestCase
{
    public function testRegisterAndGetService()
    {
        // Arrange
        $serviceName  = 'testService';
        $serviceValue = 'test-value';

        // Act
        ServiceManager::register($serviceName, function () use ($serviceValue) {
            return $serviceValue;
        });

        // Assert
        $this->assertTrue(ServiceManager::has($serviceName));
        $this->assertEquals($serviceValue, ServiceManager::get($serviceName));
    }

    public function testRegisterSingleton()
    {
        // Arrange
        $serviceName = 'testSingleton';
        $count       = 0;

        // Act
        ServiceManager::singleton($serviceName, function () use (&$count) {
            $count++;

            return "Instance $count";
        });

        // Assert - should only be created once
        $result1 = ServiceManager::get($serviceName);
        $result2 = ServiceManager::get($serviceName);

        $this->assertEquals("Instance 1", $result1);
        $this->assertEquals("Instance 1", $result2);
        $this->assertEquals(1, $count);
    }

    public function testRegisterWithParameters()
    {
        // Arrange
        $serviceName = 'parameterService';

        // Act
        ServiceManager::register($serviceName, function ($param1, $param2) {
            return "$param1 $param2";
        });

        // Assert
        $this->assertEquals('Hello World', ServiceManager::get($serviceName, 'Hello', 'World'));
    }

    public function testInstanceMethod()
    {
        // Arrange
        $serviceName    = 'instanceTest';
        $instance       = new \stdClass();
        $instance->prop = 'value';

        // Act
        ServiceManager::instance($serviceName, $instance);

        // Assert
        $result = ServiceManager::get($serviceName);
        $this->assertSame($instance, $result);
        $this->assertEquals('value', $result->prop);
    }

    public function testClearMethod()
    {
        // Arrange
        ServiceManager::register('service1', function () {
            return 'value1';
        });
        ServiceManager::register('service2', function () {
            return 'value2';
        });

        // Act
        ServiceManager::clear();

        // Assert
        $this->assertFalse(ServiceManager::has('service1'));
        $this->assertFalse(ServiceManager::has('service2'));
    }

    public function testHasMethod()
    {
        // Arrange & Act
        ServiceManager::register('existingService', function () {
            return 'exists';
        });

        // Assert
        $this->assertTrue(ServiceManager::has('existingService'));
        $this->assertFalse(ServiceManager::has('nonExistingService'));
    }

    public function testGetNonExistingService()
    {
        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Service 'nonExisting' not registered");

        // Act
        ServiceManager::get('nonExisting');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Clear the service manager before each test
        ServiceManager::clear();
    }
}
