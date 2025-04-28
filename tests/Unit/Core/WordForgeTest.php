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
        $basePath = __DIR__.'/../../';

        // Mock WordPress hooks
        $this->mockWpFunction('add_action', true);

        // Act
        WordForge::bootstrap($basePath);

        // Assert
        $this->assertEquals($basePath, $this->getPrivateProperty(WordForge::class, 'appPath'));
    }

    /**
     * Helper method to get private property value.
     */
    protected function getPrivateProperty($class, $propertyName)
    {
        $reflector = new \ReflectionClass($class);
        $property  = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue();
    }

    /**
     * Test the config method retrieves configuration correctly.
     */
    public function testConfig()
    {
        // Arrange
        $basePath = __DIR__.'/../../';
        $this->mockWpFunction('add_action', true);

        // Set up fake config data using reflection
        WordForge::bootstrap($basePath);
        $configData = [
            'app'      => [
                'name'      => 'WordForge',
                'debug'     => true,
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
        $this->assertTrue(WordForge::config('app.debug'));
        $this->assertEquals(['TestServiceProvider'], WordForge::config('app.providers'));
        $this->assertEquals('wp_', WordForge::config('database.prefix'));

        // Test default value
        $this->assertEquals('default', WordForge::config('app.unknown', 'default'));
        $this->assertNull(WordForge::config('unknown.key'));
    }

    /**
     * Helper method to set private property value.
     */
    protected function setPrivateProperty($class, $propertyName, $value)
    {
        $reflector = new \ReflectionClass($class);
        $property  = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($value);

        return $property->getValue();
    }

    /**
     * Test the basePath method returns the correct path.
     */
    public function testBasePath()
    {
        // Arrange
        $basePath = __DIR__.'/../../';
        $this->mockWpFunction('add_action', true);
        WordForge::bootstrap($basePath);

        // Act & Assert
        $this->assertEquals($basePath, WordForge::basePath());
        $this->assertEquals($basePath.'/config', WordForge::basePath('config'));
    }

    /**
     * Test the appPath method returns the correct path.
     */
    public function testAppPath()
    {
        // Arrange
        $basePath = __DIR__.'/../../';
        $this->mockWpFunction('add_action', true);
        WordForge::bootstrap($basePath);

        // Act & Assert
        $this->assertEquals($basePath, WordForge::appPath());
        $this->assertEquals($basePath.'/config', WordForge::appPath('config'));
    }

    /**
     * Test the url method generates URLs correctly.
     */
    public function testUrl()
    {
        // Arrange
        $basePath = __DIR__.'/../../';
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

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the WordForge internal state
        $reflector = new \ReflectionClass(WordForge::class);

        // Reset bootstrapped flag
        $bootstrappedProperty = $reflector->getProperty('bootstrapped');
        $bootstrappedProperty->setAccessible(true);
        $bootstrappedProperty->setValue(null, false);

        // Reset config
        $configProperty = $reflector->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, []);
    }
}
