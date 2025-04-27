# WordForge: An Opinionated SLIM MVC Framework for WordPress

WordForge is a simple, opinionated SLIM MVC framework for WordPress that brings structure to plugin development. While WordPress is a powerful platform, plugins often become unwieldy and disorganized as they grow in complexity. WordForge addresses this problem by providing a clear architectural pattern inspired by Laravel, but with zero third-party dependencies.

This skeleton framework doesn't attempt to address every edge case but instead offers a solid foundation that you can build upon and extend when needed. It brings sanity to WordPress plugin development through consistent structure and patterns.

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
- **Service Providers** for organizing your plugin's bootstrap process
- **Facades** for cleaner static interfaces
- **Simple Middleware System** for filtering HTTP requests
- **WordPress REST API Integration** with elegant request/response handling

## Installation

You can install the package via composer:

```bash
composer require codemystify/wordforge
```

## Getting Started

### Basic Setup

After installing the package, you need to initialize WordForge in your plugin:

1. Create a main plugin file:

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

// Define constants
define('MY_APP_PATH', plugin_dir_path(__FILE__));

// Load Composer autoloader
require_once MY_APP_PATH . 'vendor/autoload.php';

// Bootstrap WordForge
define('WORDFORGE_PATH', MY_APP_PATH . 'vendor/codemystify/wordforge');
require_once WORDFORGE_PATH . '/bootstrap.php';

// Load routes
require_once MY_APP_PATH . 'routes/api.php';
```

### Creating Routes

Create a `routes/api.php` file in your plugin directory:

```php
<?php

use WordForge\Support\Facades\Route;

// Simple route with a callback
Route::get('hello', function() {
    return Response::json([
        'message' => 'Hello, WordForge!'
    ]);
});

// Route with a controller
Route::get('users', 'App\\Controllers\\UserController@index');

// Route with parameters
Route::get('users/{id}', 'App\\Controllers\\UserController@show');

// Route group with middleware
Route::group(['middleware' => 'auth'], function() {
    Route::post('posts', 'App\\Controllers\\PostController@store');
    Route::put('posts/{id}', 'App\\Controllers\\PostController@update');
    Route::delete('posts/{id}', 'App\\Controllers\\PostController@destroy');
});

// Resource route
Route::resource('products', 'App\\Controllers\\ProductController');

// API Resource route (no create/edit endpoints)
Route::apiResource('api/products', 'App\\Controllers\\Api\\ProductController');
```

### Creating Controllers

Create a controller in your plugin:

```php
<?php

namespace App\Controllers;

use WordForge\Http\Controllers\Controller;
use WordForge\Http\Request;

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
        $users = \WordForge\Database\QueryBuilder::table('users')
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
        
        $user = \WordForge\Database\QueryBuilder::table('users')
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

### Middleware

Create middleware to filter requests:

```php
<?php

namespace App\Middleware;

use WordForge\Http\Middleware\Middleware;
use WordForge\Http\Request;

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
    Route::get('admin/settings', 'App\\Controllers\\AdminController@settings');
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
```

## Advanced Usage

### Service Providers

Create a service provider to bootstrap components:

```php
<?php

namespace App\Providers;

use WordForge\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register services
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Bootstrap components
    }
}
```

Register the service provider in your config file:

```php
// config/app.php
return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
    ],
];
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

## Project Structure

Here's a recommended project structure for a WordForge-based plugin:

```
my-wordforge-plugin/
├── app/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Providers/
│   ├── Requests/
│   └── Rules/
├── config/
│   └── app.php
├── routes/
│   └── api.php
├── views/
│   └── admin/
│       └── settings.php
├── composer.json
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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- CodeMystify Team