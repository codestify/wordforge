<?php
/**
 * Plugin Name: Example WordForge Plugin
 * Description: Example of a plugin using WordForge framework
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Step 1: Define plugin path constants
define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MY_PLUGIN_URL', plugin_url(__FILE__));

// Step 2: Load Composer autoloader
require_once MY_PLUGIN_PATH . 'vendor/autoload.php';

// Step 3: Bootstrap WordForge - Just one line!
\WordForge\WordForge::bootstrap(MY_PLUGIN_PATH);

// That's it! The rest of your plugin can now use WordForge features:
// - Routes will be loaded from MY_PLUGIN_PATH/routes/api.php
// - Views will be loaded from MY_PLUGIN_PATH/views/
// - Config will be loaded from MY_PLUGIN_PATH/config/
// - Service Providers will be registered from config/app.php providers array
