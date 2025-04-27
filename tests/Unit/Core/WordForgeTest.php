<?php

namespace Tests\Unit\Core;

use Tests\TestCase;
use WordForge\WordForge;

class WordForgeTest extends TestCase
{
    /**
     * Test the bootstrap method initializes the framework correctly.
     */
    public function testBootstrap()
    {
        // Arrange
        $basePath = __DIR__ . '/../../';

        // Mock WordPress hooks
        $this->mockWpFunction('add_action', true);

        // Act
        WordForge::bootstrap($basePath);

        // Assert
        $this->assertEquals($basePath, $this->getPrivateProperty(WordForge::class, 'basePath'));
    }

    /**
     * Test the config method retrieves configuration correctly.
     */
    public function testConfig()
    {
        // Arrange
        $basePath = __DIR__ . '/../../';
        $this->mockWpFunction('add_action', true);

        // Set up fake config data using reflection
        WordForge::bootstrap($basePath);
        $configData = [
            'app' => [
                'name' => 'WordForge',
                'debug' => true,
                'providers' => [
                    'TestServiceProvider'
                ]
            ],
            'database' => [
                'prefix' => 'wp_'
            ]
        ];
        $this->setPrivateProperty(WordForge::class, 'config', $configData);

        // Act & Assert
        $this->assertEquals('WordForge', WordForge::config('app.name'));
        $this->assertEquals(true, WordForge::config('app.debug'));
        $this->assertEquals(['TestServiceProvider'], WordForge::config('app.providers'));
        $this->assertEquals('wp_', WordForge::config('database.prefix'));

        // Test default value
        $this->assertEquals('default', WordForge::config('app.unknown', 'default'));
        $this->assertNull(WordForge::config('unknown.key'));
    }

    /**
     * Test the basePath method returns the correct path.
     */
    public function testBasePath()
    {
        // Arrange
        $basePath = __DIR__ . '/../../';
        $this->mockWpFunction('add_action', true);
        WordForge::bootstrap($basePath);

        // Act & Assert
        $this->assertEquals($basePath, WordForge::basePath());
        $this->assertEquals($basePath . '/config', WordForge::basePath('config'));
    }

    /**
     * Test the assetUrl method returns the correct URL.
     */
    public function testAssetUrl()
    {
        // Arrange
        $basePath = __DIR__ . '/../../';
        $this->mockWpFunction('add_action', true);
        $this->mockWpFunction('plugins_url', 'https://example.com/wp-content/plugins/wordforge/assets/css/styles.css');

        WordForge::bootstrap($basePath);

        // Act
        $url = WordForge::assetUrl('css/styles.css');

        // Assert
        $this->assertEquals('https://example.com/wp-content/plugins/wordforge/assets/css/styles.css', $url);
    }


    public function testRegisterServiceProvider()
    {
        // Arrange
        $basePath = __DIR__ . '/../../';
        $this->mockWpFunction('add_action', true);
        WordForge::bootstrap($basePath);

        // Method 1: Use a real class instead of a mock
        // Create a concrete test service provider
        $testProvider = new class extends \WordForge\Support\ServiceProvider {
            public $registerCalled = false;
            public $bootCalled = false;

            public function register(): void {
                $this->registerCalled = true;
            }

            public function boot(): void {
                $this->bootCalled = true;
            }
        };

        $providerClass = get_class($testProvider);

        // Act
        WordForge::registerServiceProvider($providerClass);

        // Assert
        $providers = $this->getPrivateProperty(WordForge::class, 'serviceProviders');
        $this->assertArrayHasKey($providerClass, $providers);


        $registeredProvider = $providers[$providerClass];
        $this->assertTrue($registeredProvider->registerCalled);
        $this->assertTrue($registeredProvider->bootCalled);
    }

    /**
     * Test the url method generates URLs correctly.
     */
    public function testUrl()
    {
        // Arrange
        $basePath = __DIR__ . '/../../';
        $this->mockWpFunction('add_action', true);
        $this->mockWpFunction('rest_url', 'https://example.com/wp-json/wordforge/v1');

        WordForge::bootstrap($basePath);
        $this->setPrivateProperty(WordForge::class, 'config', [
            'app' => [
                'api_prefix' => 'wordforge/v1'
            ]
        ]);

        // Act
        $url = WordForge::url('users', ['id' => 1]);

        // Assert
        $this->assertEquals('https://example.com/wp-json/wordforge/v1/users?id=1', $url);
    }

    /**
     * Test the version method returns the correct version.
     */
    public function testVersion()
    {
        // Act
        $version = WordForge::version();

        // Assert
        $this->assertEquals('1.0.0', $version);
    }

    /**
     * Helper method to get private property value.
     */
    protected function getPrivateProperty($class, $propertyName)
    {
        $reflector = new \ReflectionClass($class);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue();
    }

    /**
     * Helper method to set private property value.
     */
    protected function setPrivateProperty($class, $propertyName, $value)
    {
        $reflector = new \ReflectionClass($class);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($value);

        return $property->getValue();
    }
}