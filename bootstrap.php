<?php

/**
 * WordForge - Laravel-inspired MVC Framework for WordPress
 *
 * Bootstrap file to initialize the framework.
 * For direct initialization, use WordForge::bootstrap() instead.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define WordForge base path (framework path)
if (!defined('WORDFORGE_PATH')) {
    define('WORDFORGE_PATH', __DIR__);
}

// Detect application base path (plugin root directory)
if (!defined('WORDFORGE_APP_PATH')) {
    // Check if we're in vendor directory
    if (basename(dirname(WORDFORGE_PATH)) === 'vendor') {
        // We're likely in vendor/codemystify/wordforge
        // So app path should be 3 levels up or directory specified in plugin
        $app_path = defined('MY_APP_PATH') ? MY_APP_PATH : dirname(dirname(dirname(WORDFORGE_PATH)));
        define('WORDFORGE_APP_PATH', $app_path);
    } else {
        // We're not in vendor, so app path is same as framework path
        define('WORDFORGE_APP_PATH', WORDFORGE_PATH);
    }
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

// Bootstrap the framework using the static method
\WordForge\WordForge::bootstrap(WORDFORGE_APP_PATH);
