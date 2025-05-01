<?php

namespace Tests\Unit\Http;

use Tests\TestCase;
use WordForge\Http\Request;

class RequestTest extends TestCase
{
    /**
     * Request instance for testing
     */
    protected Request $request;

    /**
     * WordPress request mock
     */
    protected \WP_REST_Request $wpRequest;

    /**
     * Test that getWordPressRequest returns the original WordPress request.
     */
    public function testGetWordPressRequest(): void
    {
        $this->assertSame($this->wpRequest, $this->request->getWordPressRequest());
    }

    public function testAll(): void
    {
        // Method 1: Use a concrete WP_REST_Request with body params
        // and a custom implementation for get_file_params
        $wpRequest = new \WP_REST_Request();
        $wpRequest->set_body_param('name', 'Test User');
        $wpRequest->set_body_param('email', 'test@example.com');

        // Create a custom subclass to handle file params
        $wpRequest = new class extends \WP_REST_Request {
            public function get_params(): array
            {
                // Return the body params directly
                return $this->body_params;
            }

            public function get_file_params(): array
            {
                return ['avatar' => ['tmp_name' => '/tmp/test.jpg']];
            }
        };

        // Set the body params on our custom class
        $wpRequest->set_body_param('name', 'Test User');
        $wpRequest->set_body_param('email', 'test@example.com');

        $request = new Request($wpRequest);

        // Act
        $result = $request->all();

        // Assert
        $expected = [
            'name'   => 'Test User',
            'email'  => 'test@example.com',
            'avatar' => ['tmp_name' => '/tmp/test.jpg']
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the input method returns a specific input value.
     */
    public function testInput(): void
    {
        // Create a fresh instance with concrete data
        $wpRequest = new \WP_REST_Request();
        $wpRequest->set_body_param('name', 'Test User');
        $wpRequest->set_body_param('email', 'test@example.com');

        // Create the request with this concrete WP_REST_Request
        $request = new \WordForge\Http\Request($wpRequest);

        // Act & Assert
        $this->assertEquals('Test User', $request->input('name'));
        $this->assertEquals('test@example.com', $request->input('email'));
        $this->assertEquals('default', $request->input('unknown', 'default'));
        $this->assertNull($request->input('unknown'));
    }

    /**
     * Test the only method returns only specified keys.
     */
    public function testOnly(): void
    {
        // Arrange with concrete data
        $wpRequest = new \WP_REST_Request();
        $wpRequest->set_body_param('name', 'Test User');
        $wpRequest->set_body_param('email', 'test@example.com');
        $wpRequest->set_body_param('role', 'admin');

        $request = new Request($wpRequest);

        // Act
        $result = $request->only(['name', 'email']);

        // Assert
        $this->assertEquals(['name' => 'Test User', 'email' => 'test@example.com'], $result);
    }

    /**
     * Test the except method returns all keys except specified ones.
     */
    public function testExcept(): void
    {
        // Arrange with concrete data
        $wpRequest = new \WP_REST_Request();
        $wpRequest->set_body_param('name', 'Test User');
        $wpRequest->set_body_param('email', 'test@example.com');
        $wpRequest->set_body_param('role', 'admin');

        $request = new Request($wpRequest);

        // Act
        $result = $request->except(['role']);

        // Assert
        $this->assertEquals(['name' => 'Test User', 'email' => 'test@example.com'], $result);
    }

    /**
     * Test the has method checks if input has specified keys.
     */
    public function testHas(): void
    {
        // Arrange with concrete data
        $wpRequest = new \WP_REST_Request();
        $wpRequest->set_body_param('name', 'Test User');
        $wpRequest->set_body_param('email', 'test@example.com');

        $request = new Request($wpRequest);

        // Act & Assert
        $this->assertTrue($request->has('name'));
        $this->assertTrue($request->has(['name', 'email']));
        $this->assertFalse($request->has(['name', 'unknown']));
    }

    /**
     * Test the header method returns header values.
     */
    public function testHeader(): void
    {
        // Arrange with mocked headers
        $headers = [
            'content-type'  => ['application/json'],
            'authorization' => ['Bearer token123']
        ];

        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_headers']);
        $wpMock->method('get_headers')->willReturn($headers);

        $request = new Request($wpMock);

        // Act & Assert
        $this->assertEquals('application/json', $request->header('Content-Type'));
        $this->assertEquals('default', $request->header('X-Custom', 'default'));
    }

    /**
     * Test the headers method returns all headers.
     */
    public function testHeaders(): void
    {
        // Arrange with mocked headers
        $headers = [
            'content-type'  => ['application/json'],
            'authorization' => ['Bearer token123']
        ];

        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_headers']);
        $wpMock->method('get_headers')->willReturn($headers);

        $request = new Request($wpMock);

        // Act
        $result = $request->headers();

        // Assert
        $this->assertEquals($headers, $result);
    }

    /**
     * Test the param method returns route parameters.
     */
    public function testParam(): void
    {
        // Arrange with mocked URL parameters
        $params = ['id' => '123', 'slug' => 'test-post'];

        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_url_params']);
        $wpMock->method('get_url_params')->willReturn($params);

        $request = new Request($wpMock);

        // Act & Assert
        $this->assertEquals('123', $request->param('id'));
        $this->assertEquals('default', $request->param('unknown', 'default'));
    }

    /**
     * Test the params method returns all route parameters.
     */
    public function testParams(): void
    {
        // Arrange with mocked URL parameters
        $params = ['id' => '123', 'slug' => 'test-post'];

        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_url_params']);
        $wpMock->method('get_url_params')->willReturn($params);

        $request = new Request($wpMock);

        // Act
        $result = $request->params();

        // Assert
        $this->assertEquals($params, $result);
    }

    /**
     * Test the method method returns the request method.
     */
    public function testMethod(): void
    {
        // Arrange with mocked method
        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_method']);
        $wpMock->method('get_method')->willReturn('POST');

        $request = new Request($wpMock);

        // Act
        $result = $request->method();

        // Assert
        $this->assertEquals('POST', $result);
    }

    /**
     * Test the ajax method determines if request is AJAX.
     */
    public function testAjax(): void
    {
        // Cannot easily test this since it uses the DOING_AJAX constant
        // For now, we're just making sure it doesn't error
        $result = $this->request->ajax();
        $this->assertIsBool($result);
    }

    /**
     * Test the secure method determines if request is HTTPS.
     */
    public function testSecure(): void
    {
        // Mock the is_ssl function
        $this->mockWpFunction('is_ssl', false);

        // Act
        $result = $this->request->secure();

        // Assert
        $this->assertFalse($result);

        // Test the true case
        $this->mockWpFunction('is_ssl', true);
        $this->assertTrue($this->request->secure());
    }

    /**
     * Test the uri method returns the request URI.
     */
    public function testUri(): void
    {
        // Arrange with mocked route
        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_route']);
        $wpMock->method('get_route')->willReturn('/wordforge/v1/posts');

        $request = new Request($wpMock);

        // Act
        $result = $request->uri();

        // Assert
        $this->assertEquals('/wordforge/v1/posts', $result);
    }

    /**
     * Test the url method returns the full URL.
     */
    public function testUrl(): void
    {
        // Arrange with mocked route
        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_route']);
        $wpMock->method('get_route')->willReturn('/wordforge/v1/posts');

        $request = new Request($wpMock);

        $this->mockWpFunction('is_ssl', false);

        // This is a bit hacky but needed to test with $_SERVER
        $backedUpServer       = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'example.com';

        // Act
        $result = $request->url();

        // Assert
        $this->assertEquals('http://example.com/wordforge/v1/posts', $result);

        // Restore $_SERVER
        $_SERVER = $backedUpServer;
    }

    /**
     * Test the getContent method returns the request body.
     */
    public function testGetContent(): void
    {
        // Arrange with mocked body
        $wpMock = $this->createPartialMock(\WP_REST_Request::class, ['get_body']);
        $wpMock->method('get_body')->willReturn('{"name":"Test User"}');

        $request = new Request($wpMock);

        // Act
        $result = $request->getContent();

        // Assert
        $this->assertEquals('{"name":"Test User"}', $result);
    }

    /**
     * Test the setAttribute and getAttribute methods.
     */
    public function testAttributes(): void
    {
        // Act & Assert for setAttribute
        $this->assertSame($this->request, $this->request->setAttribute('user.id', 123));

        // Act & Assert for getAttribute
        $this->assertEquals(123, $this->request->getAttribute('user.id'));
        $this->assertEquals('default', $this->request->getAttribute('unknown', 'default'));
    }

    /**
     * Test the getAttributes method returns all attributes.
     */
    public function testGetAttributes(): void
    {
        // Arrange
        $this->request->setAttribute('user.id', 123);
        $this->request->setAttribute('user.role', 'admin');

        // Act
        $result = $this->request->getAttributes();

        // Assert
        $this->assertEquals(['user.id' => 123, 'user.role' => 'admin'], $result);
    }

    /**
     * Test the toArray method returns all input as array.
     */
    public function testToArray(): void
    {
        // Arrange with concrete data
        $wpRequest = new \WP_REST_Request();
        $wpRequest->set_body_param('name', 'Test User');
        $wpRequest->set_body_param('email', 'test@example.com');

        $request = new Request($wpRequest);

        // Act
        $result = $request->toArray();

        // Assert
        $this->assertEquals(['name' => 'Test User', 'email' => 'test@example.com'], $result);
    }

    /**
     * Test the isAuthenticated method checks if user is logged in.
     */
    public function testIsAuthenticated(): void
    {
        // Arrange
        $this->mockWpFunction('is_user_logged_in', false);

        // Act & Assert
        $this->assertFalse($this->request->isAuthenticated());

        // Test the true case
        $this->mockWpFunction('is_user_logged_in', true);
        $this->assertTrue($this->request->isAuthenticated());
    }

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Use a concrete WP_REST_Request instead of a PHPUnit mock
        $this->wpRequest = new \WP_REST_Request();
        $this->request   = new Request($this->wpRequest);
    }
}