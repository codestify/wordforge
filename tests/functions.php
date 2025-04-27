<?php

/**
 * WordForge Test Functions
 *
 * Global helper functions for testing.
 */

/**
 * Get a private or protected property from an object
 *
 * @param object|string $object Object instance or class name
 * @param string $propertyName Property name
 * @return mixed
 */
function test_get_property($object, $propertyName) {
    $reflection = is_object($object) ? new ReflectionObject($object) : new ReflectionClass($object);
    $property = $reflection->getProperty($propertyName);
    $property->setAccessible(true);

    return $property->getValue(is_object($object) ? $object : null);
}

/**
 * Set a private or protected property on an object
 *
 * @param object|string $object Object instance or class name
 * @param string $propertyName Property name
 * @param mixed $value New value
 * @return void
 */
function test_set_property($object, $propertyName, $value) {
    $reflection = is_object($object) ? new ReflectionObject($object) : new ReflectionClass($object);
    $property = $reflection->getProperty($propertyName);
    $property->setAccessible(true);

    $property->setValue(is_object($object) ? $object : null, $value);
}

/**
 * Call a private or protected method on an object
 *
 * @param object $object Object instance
 * @param string $methodName Method name
 * @param array $arguments Method arguments
 * @return mixed
 */
function test_call_method($object, $methodName, array $arguments = []) {
    $reflection = new ReflectionObject($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $arguments);
}

/**
 * Create a test fixture (JSON, XML, HTML)
 *
 * @param string $name Fixture name (without extension)
 * @param string $type Fixture type (json, xml, html, etc.)
 * @param mixed $data Data to store in fixture
 * @return string The path to the fixture file
 */
function test_create_fixture($name, $type, $data) {
    $fixturesDir = __DIR__ . '/fixtures';

    if (!is_dir($fixturesDir)) {
        mkdir($fixturesDir, 0777, true);
    }

    $filePath = "{$fixturesDir}/{$name}.{$type}";

    if ($type === 'json') {
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    } elseif ($type === 'xml') {
        $xml = new SimpleXMLElement('<root/>');
        array_walk_recursive($data, function($value, $key) use ($xml) {
            $xml->addChild($key, $value);
        });
        file_put_contents($filePath, $xml->asXML());
    } else {
        file_put_contents($filePath, $data);
    }

    return $filePath;
}

/**
 * Load a test fixture
 *
 * @param string $name Fixture name (without extension)
 * @param string $type Fixture type (json, xml, html, etc.)
 * @return mixed The fixture data
 */
function test_load_fixture($name, $type) {
    $fixturesDir = __DIR__ . '/fixtures';
    $filePath = "{$fixturesDir}/{$name}.{$type}";

    if (!file_exists($filePath)) {
        throw new RuntimeException("Fixture not found: {$filePath}");
    }

    $content = file_get_contents($filePath);

    if ($type === 'json') {
        return json_decode($content, true);
    } elseif ($type === 'xml') {
        return simplexml_load_string($content);
    } else {
        return $content;
    }
}

/**
 * Assert that two SQL queries are equivalent
 *
 * Normalizes whitespace and case for SQL keywords before comparison
 *
 * @param string $expected Expected SQL query
 * @param string $actual Actual SQL query
 * @param string $message Optional failure message
 * @return void
 */
function assert_sql_equals($expected, $actual, $message = '') {
    // List of SQL keywords to normalize
    $sqlKeywords = [
        'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN',
        'INNER JOIN', 'ORDER BY', 'GROUP BY', 'HAVING', 'LIMIT', 'OFFSET',
        'INSERT INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE FROM'
    ];

    // Function to normalize SQL
    $normalizeSQL = function($sql) use ($sqlKeywords) {
        // Normalize whitespace
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        // Uppercase SQL keywords
        foreach ($sqlKeywords as $keyword) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $sql = preg_replace($pattern, strtoupper($keyword), $sql);
        }

        return $sql;
    };

    $normalizedExpected = $normalizeSQL($expected);
    $normalizedActual = $normalizeSQL($actual);

    if ($normalizedExpected !== $normalizedActual) {
        $defaultMessage = "SQL queries do not match.\nExpected: {$normalizedExpected}\nActual: {$normalizedActual}";
        $errorMessage = $message ?: $defaultMessage;
        PHPUnit\Framework\Assert::fail($errorMessage);
    }

    // If we get here, they match
    PHPUnit\Framework\Assert::assertTrue(true);
}

/**
 * Strips whitespace from a string
 * Useful for comparing HTML output without worrying about formatting
 *
 * @param string $string The input string
 * @return string The string with whitespace removed
 */
function test_strip_whitespace($string) {
    return preg_replace('/\s+/', '', $string);
}

/**
 * Creates a test object with dynamically assigned properties
 *
 * @param array $properties Associative array of properties
 * @return object
 */
function test_object(array $properties = []) {
    $object = new stdClass();

    foreach ($properties as $property => $value) {
        $object->$property = $value;
    }

    return $object;
}

/**
 * Creates a mock of global WP_User
 *
 * @param int $id User ID
 * @param string $role User role
 * @param array $capabilities User capabilities
 * @return object
 */
function test_mock_user($id = 1, $role = 'administrator', array $capabilities = []) {
    $user = test_object([
        'ID' => $id,
        'user_login' => "user{$id}",
        'user_email' => "user{$id}@example.com",
        'user_nicename' => "User {$id}",
        'display_name' => "User {$id}",
        'roles' => [$role]
    ]);

    // Add default capabilities for role
    $defaultCapabilities = [];

    if ($role === 'administrator') {
        $defaultCapabilities = [
            'manage_options' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true
        ];
    } elseif ($role === 'editor') {
        $defaultCapabilities = [
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true
        ];
    } elseif ($role === 'author') {
        $defaultCapabilities = [
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true
        ];
    }

    $user->allcaps = array_merge($defaultCapabilities, $capabilities);

    return $user;
}