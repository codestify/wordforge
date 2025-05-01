<?php

/**
 * WordForge Test Bootstrap
 *
 * This file sets up an isolated testing environment for WordForge
 * that works completely outside of WordPress.
 */

// Define the test start time for benchmarking
define('WORDFORGE_TEST_START_TIME', microtime(true));

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define WordForge path
define('WORDFORGE_PATH', dirname(__DIR__));

// Define a flag to indicate we're in testing mode
if (!defined('WORDFORGE_TESTING')) {
    define('WORDFORGE_TESTING', true);
}

// Define core WordPress constants needed by the plugin
// These are minimal definitions that allow the plugin to run outside WordPress
define('ABSPATH', '');
define('WPINC', '');
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Include test helper functions
require_once __DIR__ . '/functions.php';

// Load WordPress function and class mocks
require_once __DIR__ . '/mocks/wp-functions.php';
require_once __DIR__ . '/mocks/wp-classes.php';

// Initialize global WordPress database object
global $wpdb;
$wpdb = new WP_DB();

// Initialize WordPress REST API server mock
global $wp_rest_server;
$wp_rest_server = new WP_REST_Server();

// Debugging: List all test files
echo "Loading test files:\n";
$testFiles = glob(__DIR__ . '/Unit/**/*Test.php');
foreach ($testFiles as $file) {
    echo "- " . str_replace(__DIR__ . '/', '', $file) . "\n";
}
echo "\n";

// Register a shutdown function to capture fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\nFATAL ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n\n";
        
        // Print the class that was being processed
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        echo "Call stack:\n";
        foreach ($trace as $frame) {
            echo "- " . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') . " in " . ($frame['file'] ?? 'unknown') . " line " . ($frame['line'] ?? 'unknown') . "\n";
        }
    }
});

// Output initialization info
echo "WordForge Standalone Test Environment Initialized\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Time Limit: " . ini_get('max_execution_time') . "s\n";
echo str_repeat('-', 80) . "\n\n";
