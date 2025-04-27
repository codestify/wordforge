<?php

namespace Tests\Unit\Http;

use Tests\TestCase;
use WordForge\Http\Response;

class ResponseTest extends TestCase
{
    /**
     * Test the constructor sets up the response correctly.
     */
    public function testConstructor()
    {
        // Arrange & Act
        $data = ['name' => 'Test User'];
        $status = 201;
        $headers = ['Content-Type' => 'application/json'];

        $response = new Response($data, $status, $headers);

        // Assert
        $this->assertEquals($data, $response->getData());
        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($headers, $response->getHeaders());
    }

    /**
     * Test the static json method creates a JSON response.
     */
    public function testJsonMethod()
    {
        // Arrange
        $data = ['name' => 'Test User'];

        // Act
        $response = Response::json($data, 200);

        // Assert
        $this->assertEquals($data, $response->getData());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());
    }

    /**
     * Test the static success method creates a success response.
     */
    public function testSuccessMethod()
    {
        // Arrange
        $data = ['name' => 'Test User'];

        // Act
        $response = Response::success($data, 200);

        // Assert
        $expectedData = [
            'success' => true,
            'data' => $data
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());
    }

    /**
     * Test the static error method creates an error response.
     */
    public function testErrorMethod()
    {
        // Arrange
        $message = 'Invalid input';

        // Act
        $response = Response::error($message, 400);

        // Assert
        $expectedData = [
            'success' => false,
            'error' => $message
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());
    }

    /**
     * Test the static validationError method creates a validation error response.
     */
    public function testValidationErrorMethod()
    {
        // Arrange
        $errors = [
            'name' => ['The name field is required.'],
            'email' => ['The email field must be a valid email address.']
        ];

        $message = 'Validation failed';

        // Act
        $response = Response::validationError($errors, $message);

        // Assert
        $expectedData = [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());
    }

    /**
     * Test the static notFound method creates a not found response.
     */
    public function testNotFoundMethod()
    {
        // Act
        $response = Response::notFound();

        // Assert
        $expectedData = [
            'success' => false,
            'error' => 'Resource not found'
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(404, $response->getStatusCode());

        // Test with custom message
        $response = Response::notFound('Post not found');
        $this->assertEquals('Post not found', $response->getData()['error']);
    }

    /**
     * Test the static unauthorized method creates an unauthorized response.
     */
    public function testUnauthorizedMethod()
    {
        // Act
        $response = Response::unauthorized();

        // Assert
        $expectedData = [
            'success' => false,
            'error' => 'Unauthorized'
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(401, $response->getStatusCode());

        // Test with custom message
        $response = Response::unauthorized('Authentication required');
        $this->assertEquals('Authentication required', $response->getData()['error']);
    }

    /**
     * Test the static forbidden method creates a forbidden response.
     */
    public function testForbiddenMethod()
    {
        // Act
        $response = Response::forbidden();

        // Assert
        $expectedData = [
            'success' => false,
            'error' => 'Forbidden'
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(403, $response->getStatusCode());

        // Test with custom message
        $response = Response::forbidden('Access denied');
        $this->assertEquals('Access denied', $response->getData()['error']);
    }

    /**
     * Test the static noContent method creates a no content response.
     */
    public function testNoContentMethod()
    {
        // Act
        $response = Response::noContent();

        // Assert
        $this->assertNull($response->getData());
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * Test the static created method creates a created response.
     */
    public function testCreatedMethod()
    {
        // Arrange
        $data = ['id' => 1, 'name' => 'Test User'];

        // Act
        $response = Response::created($data);

        // Assert
        $expectedData = [
            'success' => true,
            'data' => $data
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Test the static accepted method creates an accepted response.
     */
    public function testAcceptedMethod()
    {
        // Arrange
        $data = ['message' => 'Job accepted'];

        // Act
        $response = Response::accepted($data);

        // Assert
        $expectedData = [
            'success' => true,
            'data' => $data
        ];

        $this->assertEquals($expectedData, $response->getData());
        $this->assertEquals(202, $response->getStatusCode());
    }

    /**
     * Test the header method adds a header to the response.
     */
    public function testHeaderMethod()
    {
        // Arrange
        $response = new Response();

        // Act
        $response->header('X-Custom', 'Value');

        // Assert
        $this->assertEquals(['X-Custom' => 'Value'], $response->getHeaders());

        // Test method chaining
        $this->assertSame($response, $response->header('Another-Header', 'Another-Value'));
        $this->assertEquals([
            'X-Custom' => 'Value',
            'Another-Header' => 'Another-Value'
        ], $response->getHeaders());
    }

    /**
     * Test the withHeaders method adds multiple headers to the response.
     */
    public function testWithHeadersMethod()
    {
        // Arrange
        $response = new Response();

        // Act
        $response->withHeaders([
            'X-Custom' => 'Value',
            'X-Another' => 'Another Value'
        ]);

        // Assert
        $this->assertEquals([
            'X-Custom' => 'Value',
            'X-Another' => 'Another Value'
        ], $response->getHeaders());

        // Test method chaining
        $this->assertSame($response, $response->withHeaders(['X-Third' => 'Third Value']));
        $this->assertEquals([
            'X-Custom' => 'Value',
            'X-Another' => 'Another Value',
            'X-Third' => 'Third Value'
        ], $response->getHeaders());
    }

    /**
     * Test the setStatusCode method sets the response status code.
     */
    public function testSetStatusCodeMethod()
    {
        // Arrange
        $response = new Response();

        // Act
        $response->setStatusCode(404);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());

        // Test method chaining
        $this->assertSame($response, $response->setStatusCode(500));
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * Test the setData method sets the response data.
     */
    public function testSetDataMethod()
    {
        // Arrange
        $response = new Response();

        // Act
        $response->setData(['name' => 'Test User']);

        // Assert
        $this->assertEquals(['name' => 'Test User'], $response->getData());

        // Test method chaining
        $this->assertSame($response, $response->setData(['id' => 1]));
        $this->assertEquals(['id' => 1], $response->getData());
    }

    /**
     * Test the toWordPress method converts to a WordPress REST API response.
     */
    public function testToWordPressMethod()
    {
        // Arrange
        $data = ['name' => 'Test User'];
        $status = 200;
        $headers = ['X-Custom' => 'Value'];

        $response = new Response($data, $status, $headers);

        // Act
        $wpResponse = $response->toWordPress();

        // Assert
        $this->assertInstanceOf(\WP_REST_Response::class, $wpResponse);
        $this->assertEquals($data, $wpResponse->get_data());
        $this->assertEquals($status, $wpResponse->get_status());
    }
}