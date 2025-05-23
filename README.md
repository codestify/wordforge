# WordForge

A Laravel-inspired routing system for WordPress REST API with PHP 8 compatibility.

## Features

- Laravel-style route definitions
- Parameter constraints and validation
- Route groups and middleware
- Resource routing
- Named routes with URL generation


## Usage

### Basic Routes

```php
use Route;

// Define a simple GET route
Route::get('posts', 'PostController@index');

// Route with parameters
Route::get('posts/{id}', 'PostController@show');

// Route with parameter constraints
Route::get('posts/{year}/{month}', 'PostController@archive')
    ->where('year', '[0-9]{4}')
    ->where('month', '[0-9]{1,2}');
```

### Route Groups

```php
Route::group(['prefix' => 'api/v1', 'middleware' => 'auth'], function () {
    Route::get('users', 'UserController@index');
    Route::post('users', 'UserController@store');
    Route::get('users/{id}', 'UserController@show');
});
```

### Resource Routes

```php
// Define a resource route
Route::resource('posts', 'PostController');

// Define an API resource route (no create/edit endpoints)
Route::apiResource('users', 'UserController');
```

### Named Routes

```php
// Named route
Route::get('posts/{slug}', 'PostController@showBySlug')->name('posts.slug');

// Generate URL by route name
$url = Route::url('posts.slug', ['slug' => 'hello-world']);
```

## Parameter Conversion

WordForge automatically converts Laravel-style route parameters to WordPress REST API compatible format:

| Laravel Format | WordPress Format |
|----------------|------------------|
| `posts/{id}` | `posts/(?P<id>[0-9]+)` |
| `posts/{slug}` | `posts/(?P<slug>[a-z0-9-]+)` |
| `posts/{postId}` | `posts/(?P<post_id>[0-9]+)` |
| `posts/{year}/{month?}` | `posts/(?P<year>[0-9]{4})(/(?P<month>[0-9]{1,2}))?` |

## Middleware

```php
// Define middleware directly on routes
Route::get('profile', 'ProfileController@show')->middleware('auth');

// You can also pass an array of middleware
Route::get('admin', 'AdminController@index')->middleware(['auth', 'admin']);
```

## Installation

```bash
composer require your-vendor/wordforge
```

## Requirements

- PHP 8.0 or higher
- WordPress 5.5 or higher: An Opinionated SLIM MVC architecture for building WordPress Plugins

[![CI Status](https://github.com/codestify/wordforge/actions/workflows/ci.yml/badge.svg)](https://github.com/codestify/wordforge/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue.svg)](https://php.net/)

WordForge is a simple, opinionated SLIM MVC framework for WordPress that brings structure to plugin development. While WordPress is a powerful platform, plugins often become unwieldy and disorganized as they grow in complexity. WordForge addresses this problem by providing a clear architectural pattern inspired by Laravel, but with zero third-party dependencies.

## Table of Contents

- [Why WordForge?](#why-wordforge)
- [Features](#features)
- [Installation](#installation)
- [Getting Started](#getting-started)
  - [Basic Setup](#basic-setup)
  - [Creating Routes](#creating-routes)
  - [Route Parameter Constraints](#route-parameter-constraints)
  - [Creating Controllers](#creating-controllers)
  - [Form Request Validation](#form-request-validation)
  - [Using the Query Builder](#using-the-query-builder)
  - [Middleware](#middleware)
  - [Helper Functions](#helper-functions)
- [Advanced Usage](#advanced-usage)
  - [Service Providers](#service-providers)
  - [Service Management](#service-management)
  - [Custom Validation Rules](#creating-custom-validation-rules)
  - [Working with Views](#working-with-views)
  - [Assets and URLs](#assets-and-urls)
  - [Configuration Management](#configuration-management)
- [Project Structure](#project-structure)
- [Philosophy](#philosophy)
- [Limitations](#limitations)
- [Testing](#testing)
- [License](#license)
- [Credits](#credits)

## Why WordForge?

WordPress plugin development often lacks structure. As plugins grow, code becomes scattered across files with no clear organization. WordForge addresses this problem by providing:

- **Clear Structure**: Know exactly where to put your code
- **Zero Dependencies**: No external packages required beyond WordPress core
- **Laravel-inspired Patterns**: Familiar patterns for developers who know Laravel
- **Maintainable Code**: Easier to maintain and extend your plugins
- **Focused Simplicity**: Intentionally lightweight - just what you need

## Features

- **Structured MVC Architecture** with controllers, views, and a simple model layer
- **Laravel-style Routing** with named routes, route groups, and resource controllers
- **Clean Query Builder** for more readable database interactions
- **Form Request Validation** to simplify data validation
- **Service Providers** with hook-based initialization, similar to Laravel's service container
- **Facades** for cleaner static interfaces
- **Simple Middleware System** for filtering HTTP requests
- **WordPress REST API Integration** with elegant request/response handling
- **Configuration Management** with environment-aware settings
- **Service Management** for organizing dependencies without a full container

## Installation

You can install the package via composer:

```bash
composer require codemystify/wordforge
```

## Getting Started

### Basic Setup

After installing the package, you need to initialize WordForge in your plugin with just one line:

```php
<?php
/**
 * Plugin Name: My WordForge App
 * Description: A WordPress plugin powered by WordForge
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path
define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Load Composer autoloader
require_once MY_PLUGIN_PATH . 'vendor/autoload.php';

// Bootstrap WordForge - One line is all you need!
\WordForge\WordForge::bootstrap(MY_PLUGIN_PATH);
```

That's it! The framework will:
1. Automatically detect if it's running from a vendor directory
2. Load configuration from your plugin's config directory
3. Register service providers from your config
4. Load routes from your routes directory
5. Handle asset URLs, views, and more

### Creating Routes

Create a `routes/api.php` file in your plugin directory:

```php
<?php

use WordForge\Support\Facades\Route;
use App\Controllers\UserController;
use App\Controllers\PostController;

// Simple route with a callback
Route::get('hello', function() {
    return Response::json([
        'message' => 'Hello, WordForge!'
    ]);
});

Route::get('users/{id}', [UserController::class, 'show']);

// Route group with middleware
Route::group(['middleware' => 'auth'], function() {
    Route::post('posts', [PostController::class, 'store']);
    Route::put('posts/{id}', [PostController::class, 'update']);
    Route::delete('posts/{id}', [PostController::class, 'destroy']);
});
```

#### Important: Using Route Facade vs. Router Class

Always use the `Route` facade rather than the `Router` class directly in your route definitions:

```php
// CORRECT: Use the Route facade
Route::get('test', function() { return ['message' => 'It works!']; });

// INCORRECT: Don't use Router class directly
// Router::get('test', function() { return ['message' => 'It works!']; });
```

#### Troubleshooting Routes

If your routes aren't working after registering your plugin and service provider:

1. **Check Namespace Configuration**: Make sure your API namespace is properly set in your config:
   ```php
   // config/app.php
   return [
       'api' => [
           'namespace' => 'your-plugin/v1',
       ],
       // ...
   ];
   ```

2. **Route Service Provider**: Ensure your RouteServiceProvider is properly registered and sets the namespace before routes are loaded:
   ```php
   class RouteServiceProvider extends ServiceProvider
   {
       public function register(): void
       {
           // Set namespace here
           Router::setNamespace($apiPrefix);
       }
       
       public function boot(): void
       {
           // Load routes here
           require_once $routesPath;
           Router::registerRoutes();
       }
       
       public function hooks(): array
       {
           // Register at priority 5 (before WordForge's own hook)
           return ['rest_api_init' => 5];
       }
   }
   ```

3. **Bootstrap Timing**: Bootstrap WordForge early in your plugin:
   ```php
   // main-plugin-file.php
   require_once 'vendor/autoload.php';
   
   // Bootstrap immediately after autoloading
   WordForge::bootstrap(__DIR__);
   ```

4. **Debug Mode**: To diagnose route issues, enable WordPress debug mode:
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

5. **Access Routes**: Routes are available at:
   ```
   https://your-site.com/wp-json/{namespace}/{route-path}
   ```

### Route Parameter Constraints

WordForge provides a Laravel-inspired way to define route parameter constraints using the `where` method. When building WordPress REST API endpoints, you should use proper regex patterns to constrain your route parameters for security and validation.

#### Common Regex Patterns for Route Parameters

While WordPress REST API normally requires complex regex syntax, WordForge simplifies this to be more Laravel-like. Here are the recommended patterns to use with the `where` method:

```php
// For numeric IDs (post_id, user_id, etc.)
Route::get('posts/{id}', [PostController::class, 'show'])
    ->where('id', '\d+');  // or '[0-9]+'

// For alphanumeric identifiers
Route::get('products/{sku}', [ProductController::class, 'show'])
    ->where('sku', '[a-zA-Z0-9]+');

// For slugs (letters, numbers, and dashes)
Route::get('categories/{slug}', [CategoryController::class, 'show'])
    ->where('slug', '[a-z0-9-]+');

// For UUIDs
Route::get('orders/{uuid}', [OrderController::class, 'show'])
    ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

// For dates (YYYY-MM-DD)
Route::get('reports/{date}', [ReportController::class, 'show'])
    ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');

// For multiple constraints
Route::get('posts/{category}/{slug}', [PostController::class, 'categoryPost'])
    ->where([
        'category' => '[a-z0-9-]+',
        'slug' => '[a-z0-9-]+'
    ]);
```

#### WordPress vs WordForge Pattern Syntax

WordForge simplifies WordPress's complex regex parameter handling:

1. **WordPress REST API** requires patterns in the format: `'(?P<parameter_name>pattern)'`
2. **WordForge** uses a Laravel-inspired syntax: `->where('parameter_name', 'pattern')`

Behind the scenes, WordForge transforms your simple constraints into the complex WordPress format, making your routes more readable and maintainable.

#### Default Parameter Patterns

WordForge provides sensible defaults for common parameter names:

- `id` parameters: matches digits (`\d+`)
- `slug` parameters: matches lowercase alphanumeric characters and dashes (`[a-z0-9-]+`)
- `uuid` parameters: matches UUID format
- Other parameters: matches anything except for slashes (`[^/]+`)

#### Best Practices

For consistency and security, we recommend following these patterns:
- For ID fields: `\d+` or `[0-9]+`
- For slug fields: `[a-z0-9-]+`
- For alphanumeric fields: `[a-zA-Z0-9]+`
- For named parameters: always apply appropriate constraints with the `where` method

Using these patterns helps ensure your routes work correctly and securely with both WordForge and the WordPress REST API.

### Creating Controllers

Create a controller in your plugin:

```php
<?php

namespace App\Controllers;

use WordForge\Http\Controllers\Controller;
use WordForge\Http\Request;
use WordForge\Database\QueryBuilder;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     *
     * @param Request $request
     * @return \WordForge\Http\Response
     */
    public function index(Request $request)
    {
        $users = QueryBuilder::table('users')
            ->select(['ID', 'display_name', 'user_email'])
            ->get();

        return $this->success($users);
    }

    /**
     * Display the specified user.
     *
     * @param Request $request
     * @return \WordForge\Http\Response
     */
    public function show(Request $request)
    {
        $id = $request->param('id');
        
        $user = QueryBuilder::table('users')
            ->where('ID', $id)
            ->first();
            
        if (!$user) {
            return $this->notFound('User not found');
        }

        return $this->success($user);
    }
}
```

### Form Request Validation

Create a form request for validation:

```php
<?php

namespace App\Requests;

use WordForge\Validation\FormRequest;

class CreatePostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:3|max:255',
            'content' => 'required',
            'status' => 'in:draft,publish,private'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => 'A post title is required',
            'content.required' => 'Post content cannot be empty',
            'status.in' => 'Status must be one of: draft, publish, private'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return current_user_can('edit_posts');
    }
}
```

And use it in your controller:

```php
<?php

namespace App\Controllers;

use WordForge\Http\Controllers\Controller;
use WordForge\Http\Request;
use App\Requests\CreatePostRequest;

class PostController extends Controller
{
    /**
     * Store a newly created post.
     *
     * @param Request $request
     * @return \WordForge\Http\Response
     */
    public function store(Request $request)
    {
        // Create and validate the form request
        $formRequest = new CreatePostRequest($request->getWordPressRequest());
        $validation = $formRequest->validate($formRequest->rules());
        
        // Check if validation failed
        if ($validation !== true) {
            return $this->validationError($validation);
        }
        
        // Get the validated data
        $validated = $formRequest->validated($formRequest->rules());
        
        $postId = wp_insert_post([
            'post_title' => $validated['title'],
            'post_content' => $validated['content'],
            'post_status' => $validated['status'],
            'post_author' => get_current_user_id(),
        ]);
        
        if (is_wp_error($postId)) {
            return $this->error($postId->get_error_message());
        }
        
        return $this->created(['id' => $postId]);
    }
}
```

### Using the Query Builder

WordForge includes a powerful query builder inspired by Laravel's Eloquent:

```php
<?php

use WordForge\Database\QueryBuilder;

// Simple select query
$posts = QueryBuilder::table('posts')
    ->where('post_status', 'publish')
    ->orderBy('post_date', 'desc')
    ->limit(10)
    ->get();

// Complex query with joins
$comments = QueryBuilder::table('comments')
    ->select(['comments.*', 'posts.post_title'])
    ->join('posts', 'comments.comment_post_ID', '=', 'posts.ID')
    ->where('comments.comment_approved', '1')
    ->orderBy('comments.comment_date', 'desc')
    ->limit(20)
    ->get();

// Insert data
$id = QueryBuilder::table('my_custom_table')
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_at' => current_time('mysql')
    ]);

// Update data
QueryBuilder::table('my_custom_table')
    ->where('id', 5)
    ->update([
        'name' => 'Jane Doe',
        'updated_at' => current_time('mysql')
    ]);

// Delete data
QueryBuilder::table('my_custom_table')
    ->where('id', 5)
    ->delete();

// Transactions
QueryBuilder::table('my_custom_table')->transaction(function($query) {
    $query->insert(['name' => 'Transaction 1']);
    $query->insert(['name' => 'Transaction 2']);
    // If any query fails, all changes will be rolled back
});
```

#### Available Query Methods

The query builder provides many methods for constructing queries:

- **Selection**: `select()`, `selectRaw()`, `distinct()`
- **Joins**: `join()`, `leftJoin()`, `rightJoin()`
- **Where Clauses**: `where()`, `orWhere()`, `whereIn()`, `whereNotIn()`, `whereNull()`, `whereNotNull()`, `whereBetween()`, `whereNotBetween()`, `whereLike()`
- **Ordering**: `orderBy()`, `orderByDesc()`, `orderByRaw()`
- **Grouping**: `groupBy()`, `having()`
- **Limiting**: `limit()`, `offset()`, `take()`, `skip()`, `paginate()`
- **Aggregates**: `count()`, `max()`, `min()`, `avg()`, `sum()`
- **Transactions**: `transaction()`, `beginTransaction()`, `commit()`, `rollback()`

### Middleware

Create middleware to filter requests:

```php
<?php

namespace App\Middleware;

use WordForge\Http\Middleware\Middleware;
use WordForge\Http\Request;
use WordForge\Support\Facades\Response;

class AdminOnlyMiddleware implements Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        if (!current_user_can('manage_options')) {
            return Response::forbidden('This endpoint is for administrators only');
        }

        return true;
    }
}
```

Register middleware in your routes:

```php
Route::group(['middleware' => 'App\\Middleware\\AdminOnlyMiddleware'], function() {
    Route::get('admin/settings', [AdminController::class, 'settings']);
});
```

### Helper Functions

WordForge provides several helper functions to simplify common tasks:

```php
// Get the current request
$request = wordforge_request();

// Get a specific input value
$name = wordforge_request('name', 'default');

// Create a response
$response = wordforge_response(['data' => 'value'], 200);

// Create a JSON response
$response = wordforge_json(['success' => true]);

// Render a view
echo wordforge_view('admin.settings', ['option' => 'value']);

// Get a configuration value
$apiKey = wordforge_config('services.api.key');

// Generate a URL to a named route
$url = wordforge_url('users.show', ['id' => 1]);

// Generate a URL to an asset
$url = wordforge_asset('js/app.js');

// Get a service from the service manager
$notification = wordforge_service('notification');

// Check if a service exists
if (wordforge_has_service('mailer')) {
    $mailer = wordforge_service('mailer');
}
```

## Advanced Usage

### Service Providers

Service providers in WordForge are now even more powerful with hook-based initialization:

```php
<?php

namespace App\Providers;

use WordForge\Support\ServiceProvider;
use App\Services\Mailer;
use App\Services\NotificationService;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register a mailer service
        $this->registerSingleton('mailer', function() {
            return new Mailer(
                wordforge_config('mail.from'),
                wordforge_config('mail.name')
            );
        });
        
        // Register notification service that depends on mailer
        $this->registerSingleton('notification', function() {
            // Get the mailer dependency
            $mailer = wordforge_service('mailer');
            return new NotificationService($mailer);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Get the notification service
        $notification = wordforge_service('notification');
        
        // Register hooks
        add_action('user_register', [$notification, 'sendWelcomeEmail']);
        add_action('comment_post', [$notification, 'sendCommentNotification'], 10, 2);
    }
    
    /**
     * Specify which WordPress hooks should trigger this provider
     * 
     * @return array
     */
    public function hooks(): array
    {
        // Run this provider on plugins_loaded with priority 20
        return ['plugins_loaded' => 20];
    }
}
```

Register the service provider in your config file:

```php
// config/app.php
return [
    'providers' => [
        App\Providers\NotificationServiceProvider::class,
    ],
];
```

### Service Management

WordForge includes a lightweight service manager for managing dependencies:

```php
<?php

use WordForge\Support\ServiceManager;

// Register a service
ServiceManager::register('logger', function($channel = 'main') {
    return new Logger($channel);
});

// Register a singleton
ServiceManager::singleton('config', function() {
    return new ConfigRepository();
});

// Set an instance directly
$cache = new Cache();
ServiceManager::instance('cache', $cache);

// Check if a service exists
if (ServiceManager::has('mailer')) {
    // Get a service
    $mailer = ServiceManager::get('mailer');
}
```

### Creating Custom Validation Rules

Create a custom validation rule:

```php
<?php

namespace App\Rules;

use WordForge\Validation\Rules\Rule;

class IsWordPressAdmin implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes(string $attribute, $value): bool
    {
        $user = get_user_by('id', $value);
        
        return $user && user_can($user, 'manage_options');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a WordPress administrator.';
    }
}
```

Use the custom rule in a form request:

```php
public function rules()
{
    return [
        'user_id' => ['required', new \App\Rules\IsWordPressAdmin],
    ];
}
```

### Working with Views

Create view files in your plugin's `views` directory:

```php
<!-- views/admin/settings.php -->
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('my_plugin_settings'); ?>
        <?php do_settings_sections('my_plugin_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo esc_html__('API Key', 'my-plugin'); ?></th>
                <td>
                    <input type="text" name="my_plugin_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
```

Render views in your controller:

```php
public function settings(Request $request)
{
    $api_key = get_option('my_plugin_api_key', '');
    
    $content = wordforge_view('admin.settings', [
        'title' => 'Plugin Settings',
        'api_key' => $api_key
    ]);
    
    return $this->response($content);
}
```

### Assets and URLs

Generate URLs to assets in your plugin:

```php
// Register and enqueue a script
wp_enqueue_script(
    'my-plugin-script',
    wordforge_asset('js/app.js'),
    ['jquery'],
    '1.0.0',
    true
);

// Generate a URL to a named route
$userEditUrl = wordforge_url('users.edit', ['id' => $user->ID]);
```

### Configuration Management

Create configuration files in your plugin's `config` directory:

```php
// config/app.php
return [
    'name' => 'My Plugin',
    'api_prefix' => 'my-plugin/v1',
    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\NotificationServiceProvider::class,
    ],
];

// config/services.php
return [
    'mailchimp' => [
        'api_key' => defined('MAILCHIMP_API_KEY') ? MAILCHIMP_API_KEY : 'your-default-key',
        'list_id' => defined('MAILCHIMP_LIST_ID') ? MAILCHIMP_LIST_ID : 'your-default-list',
    ],
    'google' => [
        'analytics_id' => defined('GOOGLE_ANALYTICS_ID') ? GOOGLE_ANALYTICS_ID : 'UA-XXXXX-Y',
    ],
];
```

Access configuration values:

```php
// Get a single config value
$apiKey = wordforge_config('services.mailchimp.api_key');

// Get a config value with default
$analyticsId = wordforge_config('services.google.analytics_id', 'UA-DEFAULT');

// Get all providers
$providers = wordforge_config('app.providers');
```

## Project Structure

Here's a recommended project structure for a WordForge-based plugin:

```
my-wordforge-plugin/
├── app/
│   ├── Controllers/
│   │   ├── AdminController.php
│   │   └── ApiController.php
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── NotificationServiceProvider.php
│   ├── Requests/
│   │   └── SettingsRequest.php
│   ├── Rules/
│   │   └── CustomRule.php
│   └── Services/
│       ├── NotificationService.php
│       └── ApiService.php
├── assets/
│   ├── css/
│   │   └── app.css
│   └── js/
│       └── app.js
├── config/
│   ├── app.php
│   └── services.php
├── routes/
│   ├── api.php
│   └── admin.php
├── views/
│   ├── admin/
│   │   └── settings.php
│   └── emails/
│       └── welcome.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── vendor/
├── composer.json
├── LICENSE.md
├── README.md
└── my-wordforge-plugin.php
```

## Philosophy

WordForge is built on several key principles:

1. **Structure Over Convention**: WordPress often relies on loosely structured code. WordForge provides clear patterns for organizing your code.

2. **Simplicity First**: The framework intentionally avoids complex features and dependencies. It's designed to be easy to understand and modify.

3. **WordPress Native**: While bringing Laravel-inspired patterns, WordForge remains true to WordPress at its core, working with WordPress hooks and APIs rather than against them.

4. **Build Only What You Need**: The framework provides a foundation, but you're encouraged to extend only the parts you need for your specific project.

5. **No Magic**: WordForge avoids "magical" behaviors that hide implementation details. The code is straightforward and traceable.

## Limitations

WordForge is intentionally lightweight and doesn't try to solve every problem:

- It's not a full replacement for complex WordPress plugin architectures
- It doesn't introduce ORM or complex database abstraction
- It doesn't modify WordPress core behavior
- It's focused on backend structure rather than frontend rendering

## Testing

WordForge includes a testing setup that makes it easier to test your plugin:

1. Set up PHPUnit in your plugin:

```bash
composer require --dev phpunit/phpunit
```

2. Create a PHPUnit configuration file:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         verbose="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

3. Create a bootstrap file for your tests:

```php
<?php
// tests/bootstrap.php

require_once __DIR__ . '/../vendor/autoload.php';

// Load WordForge test framework
require_once __DIR__ . '/../vendor/codemystify/wordforge/tests/bootstrap.php';
```

4. Write your tests:

```php
<?php
// tests/Unit/ExampleTest.php

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }
}
```

5. Run your tests:

```bash
vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](LICENCE.md) for more information.

## Credits

- CodeMystify Team
