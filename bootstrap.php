<?php

/**
 * WordForge - Laravel-inspired MVC Framework for WordPress
 *
 * Bootstrap file to initialize the framework.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define WordForge base path (adjust if necessary)
if (!defined('WORDFORGE_PATH')) {
    define('WORDFORGE_PATH', __DIR__);
}

// Simple autoloader for WordForge classes
spl_autoload_register(function ($class) {
    // Only handle WordForge classes
    if (strpos($class, 'WordForge\\') !== 0) {
        return;
    }

    // Convert namespace to path
    $file = WORDFORGE_PATH . '/src/' . str_replace(['WordForge\\', '\\'], ['', '/'], $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load helper functions
$helpers_file = WORDFORGE_PATH . '/src/Support/helpers.php';
if (file_exists($helpers_file)) {
    require_once $helpers_file;
}

// Set up facade aliases for global usage
class_alias('WordForge\Support\Facades\Route', 'Route');
class_alias('WordForge\Support\Facades\Response', 'Response');
class_alias('WordForge\Support\Facades\Request', 'Request');

// Bootstrap the framework
add_action('plugins_loaded', function() {
    // Initialize the framework with the correct base path
    \WordForge\WordForge::bootstrap(WORDFORGE_PATH);
}, 5); // Priority 5 ensures it runs early but after most critical WP components

// Add hook to register routes when WordPress initializes REST API
add_action('rest_api_init', function() {
    // Register routes with WordPress REST API
    \WordForge\Http\Router\Router::registerRoutes();
});