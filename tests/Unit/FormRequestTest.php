<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use WordForge\Validation\FormRequest;

class FormRequestTest extends TestCase
{
    /**
     * Test the constructor sets up the form request correctly.
     */
    public function testConstructor()
    {
        // Arrange
        $wpRequest = $this->createMock(\WP_REST_Request::class);

        // Act
        $formRequest = $this->createFormRequest([]);

        // Assert - just verify we can create the instance
        $this->assertInstanceOf(FormRequest::class, $formRequest);
    }

    /**
     * Create a concrete implementation of the abstract FormRequest with direct data injection.
     */
    private function createFormRequest($testData = [])
    {
        // Create a minimal WP_REST_Request mock - we won't rely on its methods
        $wpRequest = $this->createMock(\WP_REST_Request::class);

        return new class($wpRequest, $testData) extends FormRequest {
            private $testData;

            public $authorizeResult = true;
            public $validationRules = ['name' => 'required'];
            public $validationMessages = [];
            public $validationAttributes = [];

            public function __construct($wpRequest, array $testData)
            {
                $this->testData = $testData;
                parent::__construct($wpRequest);
            }

            // Override all() to return our test data directly
            public function all(): array
            {
                return $this->testData;
            }

            public function rules()
            {
                return $this->validationRules;
            }

            public function messages()
            {
                return $this->validationMessages;
            }

            public function attributes()
            {
                return $this->validationAttributes;
            }

            public function authorize()
            {
                return $this->authorizeResult;
            }
        };
    }

    /**
     * Test the validate method when authorization passes.
     */
    public function testValidateWithAuthorization()
    {
        // Arrange - directly provide test data
        $formRequest                  = $this->createFormRequest(['name' => 'Test User']);
        $formRequest->authorizeResult = true;

        // Verify data is accessible
        $allData = $formRequest->all();
        $this->assertArrayHasKey('name', $allData);
        $this->assertEquals('Test User', $allData['name']);

        // Act
        $result = $formRequest->validate($formRequest->rules());

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test the validate method when authorization fails.
     */
    public function testValidateWithoutAuthorization()
    {
        // Arrange
        $formRequest                  = $this->createFormRequest(['name' => 'Test User']);
        $formRequest->authorizeResult = false;

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unauthorized');
        $formRequest->validate($formRequest->rules());
    }

    /**
     * Test the validate method when validation fails.
     */
    public function testValidateWithValidationFailure()
    {
        // Arrange
        $formRequest                  = $this->createFormRequest(['name' => '']); // Empty name
        $formRequest->authorizeResult = true;

        // Act & Assert
        $this->expectException(\WordForge\Validation\ValidationException::class);
        $formRequest->validate($formRequest->rules());
    }

    /**
     * Test the validate method with custom error messages.
     */
    public function testValidateWithCustomMessages()
    {
        // Arrange
        $formRequest                     = $this->createFormRequest(['name' => '']); // Empty name
        $formRequest->authorizeResult    = true;
        $formRequest->validationMessages = ['name.required' => 'Please provide your name'];

        // Act & Assert
        try {
            $formRequest->validate(
                $formRequest->rules(),
                $formRequest->validationMessages
            );
            $this->fail('Expected ValidationException was not thrown');
        } catch (\WordForge\Validation\ValidationException $e) {
            $errors = $e->validator()->errors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertEquals(['Please provide your name'], $errors['name']);
        }
    }

    /**
     * Test the validate method with custom attribute names.
     */
    public function testValidateWithCustomAttributes()
    {
        // Arrange
        $formRequest                       = $this->createFormRequest(['first_name' => '']); // Empty first_name
        $formRequest->validationRules      = ['first_name' => 'required'];
        $formRequest->validationAttributes = ['first_name' => 'First Name'];

        // Act & Assert
        try {
            $formRequest->validate(
                $formRequest->rules(),
                $formRequest->messages(),
                $formRequest->validationAttributes
            );
            $this->fail('Expected ValidationException was not thrown');
        } catch (\WordForge\Validation\ValidationException $e) {
            $errors = $e->validator()->errors();
            $this->assertArrayHasKey('first_name', $errors);
            $this->assertStringContainsString('First Name', $errors['first_name'][0]);
        }
    }

    /**
     * Test the validated method returns validated data.
     */
    public function testValidated()
    {
        // Arrange
        $formRequest                  = $this->createFormRequest([
            'name'  => 'Test User',
            'email' => 'test@example.com',
            'extra' => 'not in rules'
        ]);
        $formRequest->validationRules = [
            'name'  => 'required',
            'email' => 'required|email'
        ];

        // Act
        $validated = $formRequest->validated($formRequest->rules());

        // Assert
        $this->assertEquals([
            'name'  => 'Test User',
            'email' => 'test@example.com'
        ], $validated);

        // Should not contain fields not in rules
        $this->assertArrayNotHasKey('extra', $validated);
    }
}