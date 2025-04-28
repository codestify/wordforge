<?php

namespace Tests\Unit;

use Tests\TestCase;
use WordForge\ServiceProviderManager;
use WordForge\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public $registerCalled = false;
    public $bootCalled = false;

    public function register(): void
    {
        $this->registerCalled = true;
    }

    public function boot()
    {
        $this->bootCalled = true;
    }

    public function hooks(): array
    {
        return ['init' => 10];
    }
}

class ServiceProviderManagerTest extends TestCase
{
    public function testRegisterAddsProvidersToList()
    {
        // Arrange
        $provider = TestServiceProvider::class;

        // Mock the WordPress add_action function
        $actionsCalled = [];
        $this->mockWpFunction('add_action', function ($hook, $callback, $priority = 10) use (&$actionsCalled) {
            $actionsCalled[] = [
                'hook'     => $hook,
                'priority' => $priority
            ];

            return true;
        });

        // Act
        ServiceProviderManager::register([$provider]);

        // Assert
        $reflection        = new \ReflectionClass(ServiceProviderManager::class);
        $providersProperty = $reflection->getProperty('providers');
        $providersProperty->setAccessible(true);
        $providers = $providersProperty->getValue();

        $this->assertContains($provider, $providers);
    }

    public function testInitializeProviderRegistersProvider()
    {
        // Arrange
        $provider = TestServiceProvider::class;

        // Mock add_action
        $this->mockWpFunction('add_action', function ($hook, $callback, $priority = 10) {
            // Do nothing, just mock the function
            return true;
        });

        // Register the provider
        ServiceProviderManager::register([$provider]);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass(ServiceProviderManager::class);
        $method     = $reflection->getMethod('initializeProvider');
        $method->setAccessible(true);

        // Act
        $method->invoke(null, $provider);

        // Assert
        $registeredProperty = $reflection->getProperty('registered');
        $registeredProperty->setAccessible(true);
        $registered = $registeredProperty->getValue();

        $this->assertArrayHasKey($provider, $registered);
        $this->assertInstanceOf(TestServiceProvider::class, $registered[$provider]);
        $this->assertTrue($registered[$provider]->registerCalled);
        $this->assertFalse($registered[$provider]->bootCalled);
    }

    public function testBootProvidersBootsAllRegisteredProviders()
    {
        // Arrange
        $provider = TestServiceProvider::class;

        // Mock add_action
        $this->mockWpFunction('add_action', function ($hook, $callback, $priority = 10) {
            // Do nothing, just mock the function
            return true;
        });

        // Register and initialize the provider
        ServiceProviderManager::register([$provider]);

        $reflection = new \ReflectionClass(ServiceProviderManager::class);

        $initMethod = $reflection->getMethod('initializeProvider');
        $initMethod->setAccessible(true);
        $initMethod->invoke(null, $provider);

        // Act
        ServiceProviderManager::bootProviders();

        // Assert
        $registeredProperty = $reflection->getProperty('registered');
        $registeredProperty->setAccessible(true);
        $registered = $registeredProperty->getValue();

        $bootedProperty = $reflection->getProperty('booted');
        $bootedProperty->setAccessible(true);
        $booted = $bootedProperty->getValue();

        $this->assertTrue($registered[$provider]->bootCalled);
        $this->assertTrue(isset($booted[$provider]));
    }

    public function testBootProviderDoesNotBootAlreadyBootedProviders()
    {
        // Arrange
        $provider = TestServiceProvider::class;

        // Mock add_action
        $this->mockWpFunction('add_action', function ($hook, $callback, $priority = 10) {
            // Do nothing, just mock the function
            return true;
        });

        // Register and initialize the provider
        ServiceProviderManager::register([$provider]);

        $reflection = new \ReflectionClass(ServiceProviderManager::class);

        $initMethod = $reflection->getMethod('initializeProvider');
        $initMethod->setAccessible(true);
        $initMethod->invoke(null, $provider);

        // Boot once
        ServiceProviderManager::bootProviders();

        // Get the instance to reset boot flag
        $registeredProperty = $reflection->getProperty('registered');
        $registeredProperty->setAccessible(true);
        $registered                        = $registeredProperty->getValue();
        $registered[$provider]->bootCalled = false;

        // Act
        ServiceProviderManager::bootProviders();

        // Assert - boot should not be called again
        $this->assertFalse($registered[$provider]->bootCalled);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the ServiceProviderManager state
        $reflection = new \ReflectionClass(ServiceProviderManager::class);

        $providersProperty = $reflection->getProperty('providers');
        $providersProperty->setAccessible(true);
        $providersProperty->setValue(null, []);

        $registeredProperty = $reflection->getProperty('registered');
        $registeredProperty->setAccessible(true);
        $registeredProperty->setValue(null, []);

        $bootedProperty = $reflection->getProperty('booted');
        $bootedProperty->setAccessible(true);
        $bootedProperty->setValue(null, []);
    }
}
