<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use WordForge\Validation\Validator;

class ValidatorTest extends TestCase
{
    /**
     * Test the constructor sets up the validator correctly.
     */
    public function testConstructor()
    {
        // Arrange
        $data = ['name' => 'Test User', 'email' => 'test@example.com'];
        $rules = ['name' => 'required', 'email' => 'required|email'];
        $messages = ['email.required' => 'Email is required'];
        $attributes = ['name' => 'Full Name'];

        // Act
        $validator = new Validator($data, $rules, $messages, $attributes);

        // Assert - use reflection to check properties
        $reflector = new \ReflectionClass($validator);

        $dataProperty = $reflector->getProperty('data');
        $dataProperty->setAccessible(true);

        $rulesProperty = $reflector->getProperty('rules');
        $rulesProperty->setAccessible(true);

        $messagesProperty = $reflector->getProperty('messages');
        $messagesProperty->setAccessible(true);

        $attributesProperty = $reflector->getProperty('attributes');
        $attributesProperty->setAccessible(true);

        $this->assertEquals($data, $dataProperty->getValue($validator));
        $this->assertEquals($rules, $rulesProperty->getValue($validator));
        $this->assertEquals($messages, $messagesProperty->getValue($validator));
        $this->assertEquals($attributes, $attributesProperty->getValue($validator));
    }

    /**
     * Test basic validation passes.
     */
    public function testValidationPasses()
    {
        // Arrange
        $data = ['name' => 'Test User', 'email' => 'test@example.com'];
        $rules = ['name' => 'required', 'email' => 'required|email'];

        $validator = new Validator($data, $rules);

        // Act
        $result = $validator->passes();

        // Assert
        $this->assertTrue($result);
        $this->assertEmpty($validator->errors());
    }

    /**
     * Test validation fails when required field is missing.
     */
    public function testValidationFailsWithMissingRequiredField()
    {
        // Arrange
        $data = ['name' => 'Test User']; // Missing email
        $rules = ['name' => 'required', 'email' => 'required'];

        $validator = new Validator($data, $rules);

        // Act
        $result = $validator->passes();

        // Assert
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $validator->errors());
    }

    /**
     * Test validation fails when email is invalid.
     */
    public function testValidationFailsWithInvalidEmail()
    {
        // Arrange
        $data = ['name' => 'Test User', 'email' => 'invalid-email'];
        $rules = ['name' => 'required', 'email' => 'required|email'];

        $validator = new Validator($data, $rules);

        // Act
        $result = $validator->passes();

        // Assert
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $validator->errors());
    }

    /**
     * Test the fails method returns the opposite of passes.
     */
    public function testFailsMethod()
    {
        // Arrange
        $data = ['name' => 'Test User'];
        $rules = ['name' => 'required', 'email' => 'required'];

        $validator = new Validator($data, $rules);

        // Act & Assert
        $this->assertTrue($validator->fails());

        // With passing validation
        $data = ['name' => 'Test User', 'email' => 'test@example.com'];
        $validator = new Validator($data, $rules);

        $this->assertFalse($validator->fails());
    }

    /**
     * Test the error messages use custom messages when provided.
     */
    public function testCustomErrorMessages()
    {
        // Arrange
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        $messages = ['name.required' => 'Please enter your name'];

        $validator = new Validator($data, $rules, $messages);

        // Act
        $validator->passes();
        $errors = $validator->errors();

        // Assert
        $this->assertEquals(['Please enter your name'], $errors['name']);
    }

    /**
     * Test the error messages use custom attribute names when provided.
     */
    public function testCustomAttributeNames()
    {
        // Arrange
        $data = ['first_name' => ''];
        $rules = ['first_name' => 'required'];
        $attributes = ['first_name' => 'First Name'];

        $validator = new Validator($data, $rules, [], $attributes);

        // Act
        $validator->passes();
        $errors = $validator->errors();

        // Assert
        $this->assertStringContainsString('First Name', $errors['first_name'][0]);
    }

    /**
     * Test validation of required rule.
     */
    public function testRequiredRule()
    {
        // Arrange & Act & Assert

        // Test with empty string
        $validator = new Validator(['field' => ''], ['field' => 'required']);
        $this->assertFalse($validator->passes());

        // Test with null
        $validator = new Validator(['field' => null], ['field' => 'required']);
        $this->assertFalse($validator->passes());

        // Test with empty array
        $validator = new Validator(['field' => []], ['field' => 'required']);
        $this->assertFalse($validator->passes());

        // Test with non-empty value
        $validator = new Validator(['field' => 'value'], ['field' => 'required']);
        $this->assertTrue($validator->passes());

        // Test with zero
        $validator = new Validator(['field' => 0], ['field' => 'required']);
        $this->assertTrue($validator->passes());

        // Test with false
        $validator = new Validator(['field' => false], ['field' => 'required']);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation of email rule.
     */
    public function testEmailRule()
    {
        // Arrange & Act & Assert

        // Test with valid email
        $validator = new Validator(['email' => 'test@example.com'], ['email' => 'email']);
        $this->assertTrue($validator->passes());

        // Test with invalid email
        $validator = new Validator(['email' => 'invalid-email'], ['email' => 'email']);
        $this->assertFalse($validator->passes());

        // Test with empty value - should pass if not required
        $validator = new Validator(['email' => ''], ['email' => 'email']);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation of url rule.
     */
    public function testUrlRule()
    {
        // Arrange & Act & Assert

        // Test with valid URL
        $validator = new Validator(['url' => 'https://example.com'], ['url' => 'url']);
        $this->assertTrue($validator->passes());

        // Test with invalid URL
        $validator = new Validator(['url' => 'invalid-url'], ['url' => 'url']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of min rule for strings, numbers, and arrays.
     */
    public function testMinRule()
    {
        // Arrange & Act & Assert

        // Test with string length
        $validator = new Validator(['string' => 'hello'], ['string' => 'min:5']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['string' => 'hi'], ['string' => 'min:5']);
        $this->assertFalse($validator->passes());

        // Test with numeric value
        $validator = new Validator(['number' => 10], ['number' => 'min:5']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['number' => 3], ['number' => 'min:5']);
        $this->assertFalse($validator->passes());

        // Test with array items
        $validator = new Validator(['array' => [1, 2, 3, 4, 5]], ['array' => 'min:5']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['array' => [1, 2]], ['array' => 'min:5']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of max rule for strings, numbers, and arrays.
     */
    public function testMaxRule()
    {
        // Arrange & Act & Assert

        // Test with string length
        $validator = new Validator(['string' => 'hello'], ['string' => 'max:10']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['string' => 'this string is too long'], ['string' => 'max:10']);
        $this->assertFalse($validator->passes());

        // Test with numeric value
        $validator = new Validator(['number' => 10], ['number' => 'max:20']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['number' => 30], ['number' => 'max:20']);
        $this->assertFalse($validator->passes());

        // Test with array items
        $validator = new Validator(['array' => [1, 2, 3]], ['array' => 'max:5']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['array' => [1, 2, 3, 4, 5, 6]], ['array' => 'max:5']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of numeric rule.
     */
    public function testNumericRule()
    {
        // Arrange & Act & Assert

        // Test with integer
        $validator = new Validator(['field' => 123], ['field' => 'numeric']);
        $this->assertTrue($validator->passes());

        // Test with float
        $validator = new Validator(['field' => 123.45], ['field' => 'numeric']);
        $this->assertTrue($validator->passes());

        // Test with numeric string
        $validator = new Validator(['field' => '123'], ['field' => 'numeric']);
        $this->assertTrue($validator->passes());

        // Test with non-numeric
        $validator = new Validator(['field' => 'abc'], ['field' => 'numeric']);
        $this->assertFalse($validator->passes());

        // Test with mixed
        $validator = new Validator(['field' => '123abc'], ['field' => 'numeric']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of integer rule.
     */
    public function testIntegerRule()
    {
        // Arrange & Act & Assert

        // Test with integer
        $validator = new Validator(['field' => 123], ['field' => 'integer']);
        $this->assertTrue($validator->passes());

        // Test with integer string
        $validator = new Validator(['field' => '123'], ['field' => 'integer']);
        $this->assertTrue($validator->passes());

        // Test with float
        $validator = new Validator(['field' => 123.45], ['field' => 'integer']);
        $this->assertFalse($validator->passes());

        // Test with float string
        $validator = new Validator(['field' => '123.45'], ['field' => 'integer']);
        $this->assertFalse($validator->passes());

        // Test with non-numeric
        $validator = new Validator(['field' => 'abc'], ['field' => 'integer']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of boolean rule.
     */
    public function testBooleanRule()
    {
        // Arrange & Act & Assert

        // Test with boolean true
        $validator = new Validator(['field' => true], ['field' => 'boolean']);
        $this->assertTrue($validator->passes());

        // Test with boolean false
        $validator = new Validator(['field' => false], ['field' => 'boolean']);
        $this->assertTrue($validator->passes());

        // Test with integer 1
        $validator = new Validator(['field' => 1], ['field' => 'boolean']);
        $this->assertTrue($validator->passes());

        // Test with integer 0
        $validator = new Validator(['field' => 0], ['field' => 'boolean']);
        $this->assertTrue($validator->passes());

        // Test with string '1'
        $validator = new Validator(['field' => '1'], ['field' => 'boolean']);
        $this->assertTrue($validator->passes());

        // Test with string '0'
        $validator = new Validator(['field' => '0'], ['field' => 'boolean']);
        $this->assertTrue($validator->passes());

        // Test with invalid value
        $validator = new Validator(['field' => 'yes'], ['field' => 'boolean']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of array rule.
     */
    public function testArrayRule()
    {
        // Arrange & Act & Assert

        // Test with array
        $validator = new Validator(['field' => [1, 2, 3]], ['field' => 'array']);
        $this->assertTrue($validator->passes());

        // Test with empty array
        $validator = new Validator(['field' => []], ['field' => 'array']);
        $this->assertTrue($validator->passes());

        // Test with non-array
        $validator = new Validator(['field' => 'not an array'], ['field' => 'array']);
        $this->assertFalse($validator->passes());

        // Test with object (should fail)
        $validator = new Validator(['field' => (object)['key' => 'value']], ['field' => 'array']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of in rule.
     */
    public function testInRule()
    {
        // Arrange & Act & Assert

        // Test with value in the list
        $validator = new Validator(['field' => 'apple'], ['field' => 'in:apple,orange,banana']);
        $this->assertTrue($validator->passes());

        // Test with value not in the list
        $validator = new Validator(['field' => 'grape'], ['field' => 'in:apple,orange,banana']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of not_in rule.
     */
    public function testNotInRule()
    {
        // Arrange & Act & Assert

        // Test with value not in the list
        $validator = new Validator(['field' => 'grape'], ['field' => 'not_in:apple,orange,banana']);
        $this->assertTrue($validator->passes());

        // Test with value in the list
        $validator = new Validator(['field' => 'apple'], ['field' => 'not_in:apple,orange,banana']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation of date rule.
     */
    public function testDateRule()
    {
        // Arrange & Act & Assert

        // Test with valid date
        $validator = new Validator(['field' => '2023-01-01'], ['field' => 'date']);
        $this->assertTrue($validator->passes());

        // Test with DateTime object
        $validator = new Validator(['field' => new \DateTime()], ['field' => 'date']);
        $this->assertTrue($validator->passes());

        // Test with invalid date
        $validator = new Validator(['field' => 'not a date'], ['field' => 'date']);
        $this->assertFalse($validator->passes());

        // Test with invalid format
        $validator = new Validator(['field' => '01/01/2023'], ['field' => 'date']);
        $this->assertTrue($validator->passes()); // PHP's date_parse is quite forgiving
    }

    /**
     * Test validation of between rule.
     */
    public function testBetweenRule()
    {
        // Arrange & Act & Assert

        // Test with string length between
        $validator = new Validator(['field' => 'hello'], ['field' => 'between:3,10']);
        $this->assertTrue($validator->passes());

        // Test with string length too short
        $validator = new Validator(['field' => 'hi'], ['field' => 'between:3,10']);
        $this->assertFalse($validator->passes());

        // Test with string length too long
        $validator = new Validator(['field' => 'this string is too long'], ['field' => 'between:3,10']);
        $this->assertFalse($validator->passes());

        // Test with numeric value between
        $validator = new Validator(['field' => 15], ['field' => 'between:10,20']);
        $this->assertTrue($validator->passes());

        // Test with numeric value too small
        $validator = new Validator(['field' => 5], ['field' => 'between:10,20']);
        $this->assertFalse($validator->passes());

        // Test with numeric value too large
        $validator = new Validator(['field' => 25], ['field' => 'between:10,20']);
        $this->assertFalse($validator->passes());

        // Test with array size between
        $validator = new Validator(['field' => [1, 2, 3, 4]], ['field' => 'between:3,5']);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test validation of regex rule.
     */
    public function testRegexRule()
    {
        // Arrange & Act & Assert

        // Test with matching regex
        $validator = new Validator(['field' => 'abc123'], ['field' => 'regex:/^[a-z0-9]+$/']);
        $this->assertTrue($validator->passes());

        // Test with non-matching regex
        $validator = new Validator(['field' => 'ABC123!'], ['field' => 'regex:/^[a-z0-9]+$/']);
        $this->assertFalse($validator->passes());
    }

    /**
     * Test validation with multiple rules.
     */
    public function testMultipleRules()
    {
        // Arrange
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'age' => 25,
            'website' => 'https://example.com'
        ];

        $rules = [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
            'age' => 'required|integer|between:18,100',
            'website' => 'required|url'
        ];

        $validator = new Validator($data, $rules);

        // Act
        $result = $validator->passes();

        // Assert
        $this->assertTrue($result);

        // Test with invalid data
        $data = [
            'name' => 'AB', // Too short
            'email' => 'invalid-email',
            'age' => 15, // Too young
            'website' => 'not-a-url'
        ];

        $validator = new Validator($data, $rules);
        $result = $validator->passes();

        $this->assertFalse($result);
        $this->assertCount(4, $validator->errors());
    }

    /**
     * Test that non-required fields are skipped if empty.
     */
    public function testEmptyNonRequiredFields()
    {
        // Arrange
        $data = [
            'name' => 'Test User',
            'email' => '', // Empty but not required
            'age' => null, // Null but not required
            'website' => '' // Empty but not required
        ];

        $rules = [
            'name' => 'required|min:3',
            'email' => 'email',
            'age' => 'integer|between:18,100',
            'website' => 'url'
        ];

        $validator = new Validator($data, $rules);

        // Act
        $result = $validator->passes();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test parsing of rule strings.
     */
    public function testParseRules()
    {
        // Use reflection to access the protected method
        $reflector = new \ReflectionClass(Validator::class);
        $method = $reflector->getMethod('parseRules');
        $method->setAccessible(true);

        $validator = new Validator([], []);

        // Test with a single rule
        $result = $method->invoke($validator, 'required');
        $this->assertEquals([['required', []]], $result);

        // Test with multiple rules
        $result = $method->invoke($validator, 'required|email');
        $this->assertEquals([['required', []], ['email', []]], $result);

        // Test with parameters
        $result = $method->invoke($validator, 'required|min:5|between:10,20');
        $this->assertEquals([
            ['required', []],
            ['min', ['5']],
            ['between', ['10', '20']]
        ], $result);

        // Test with array input
        $result = $method->invoke($validator, ['required', 'email', 'min:5']);
        $this->assertEquals([
            ['required', []],
            ['email', []],
            ['min', ['5']]
        ], $result);
    }

    /**
     * Test parsing of a single rule.
     */
    public function testParseRule()
    {
        // Use reflection to access the protected method
        $reflector = new \ReflectionClass(Validator::class);
        $method = $reflector->getMethod('parseRule');
        $method->setAccessible(true);

        $validator = new Validator([], []);

        // Test with no parameters
        $result = $method->invoke($validator, 'required');
        $this->assertEquals(['required', []], $result);

        // Test with single parameter
        $result = $method->invoke($validator, 'min:5');
        $this->assertEquals(['min', ['5']], $result);

        // Test with multiple parameters
        $result = $method->invoke($validator, 'between:10,20');
        $this->assertEquals(['between', ['10', '20']], $result);
    }
}