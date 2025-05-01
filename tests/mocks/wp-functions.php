<?php

/**
 * WordPress Core Functions Mock
 *
 * This file provides mock implementations of commonly used WordPress functions
 * for testing purposes.
 */

// Store mocked function values for easy retrieval in tests
global $wp_mock_functions;
$wp_mock_functions = [];

/**
 * Set a mocked function return value
 */
function wp_mock_function(string $function, mixed $value): void
{
    global $wp_mock_functions;
    $wp_mock_functions[$function] = $value;
}

/**
 * Get a mocked function return value
 */
function wp_mock_value(string $function, mixed $default = null): mixed
{
    global $wp_mock_functions;

    return $wp_mock_functions[$function] ?? $default;
}

/**
 * Clear mocked function values
 */
function wp_mock_clear(?string $function = null): void
{
    global $wp_mock_functions;

    if ($function === null) {
        $wp_mock_functions = [];
    } else {
        unset($wp_mock_functions[$function]);
    }
}

// Mock WordPress action and filter functions
if (! function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        return true;
    }
}

if (! function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        return true;
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args)
    {
        return wp_mock_value("apply_filters_{$hook}", $value);
    }
}

if (! function_exists('do_action')) {
    function do_action($hook, ...$args)
    {
        return true;
    }
}

if (! function_exists('remove_action')) {
    function remove_action($hook, $callback, $priority = 10)
    {
        return true;
    }
}

if (! function_exists('remove_filter')) {
    function remove_filter($hook, $callback, $priority = 10)
    {
        return true;
    }
}

// WordPress plugin functions
if (! function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '')
    {
        return wp_mock_value('plugins_url', "http://example.com/wp-content/plugins/{$path}");
    }
}

if (! function_exists('plugin_dir_path')) {
    function plugin_dir_path($file)
    {
        return dirname($file) . '/';
    }
}

if (! function_exists('plugin_dir_url')) {
    function plugin_dir_url($file)
    {
        return wp_mock_value('plugin_dir_url', "http://example.com/wp-content/plugins/" . basename(dirname($file)) . '/');
    }
}

// WordPress REST API functions
if (! function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = [], $override = false)
    {
        return true;
    }
}

if (! function_exists('rest_url')) {
    function rest_url($path = '')
    {
        return wp_mock_value('rest_url', "http://example.com/wp-json/" . ltrim($path, '/'));
    }
}

if (! function_exists('rest_ensure_response')) {
    function rest_ensure_response($response)
    {
        if (is_wp_error($response)) {
            return $response;
        }

        if ($response instanceof WP_REST_Response) {
            return $response;
        }

        return new WP_REST_Response($response);
    }
}

// WordPress utility functions
if (! function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = [])
    {
        if (is_object($args)) {
            $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed_args = $args;
        } else {
            parse_str($args, $parsed_args);
        }

        return array_merge($defaults, $parsed_args);
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($str)
    {
        return trim(strip_tags($str));
    }
}

if (! function_exists('wp_kses_post')) {
    function wp_kses_post($content)
    {
        return strip_tags($content, '<p><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code>');
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_url')) {
    function esc_url($url)
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

// WordPress capability functions
if (! function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return wp_mock_value('current_user_can_' . $capability, true);
    }
}

if (! function_exists('is_user_logged_in')) {
    function is_user_logged_in()
    {
        return wp_mock_value('is_user_logged_in', true);
    }
}

if (! function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        $user             = new stdClass();
        $user->ID         = wp_mock_value('current_user_id', 1);
        $user->user_login = wp_mock_value('current_user_login', 'admin');
        $user->user_email = wp_mock_value('current_user_email', 'admin@example.com');

        return $user;
    }
}

// WordPress HTTP functions
if (! function_exists('is_ssl')) {
    function is_ssl()
    {
        return wp_mock_value('is_ssl', false);
    }
}

// WordPress options functions
if (! function_exists('get_option')) {
    function get_option($option, $default = false)
    {
        return wp_mock_value('option_' . $option, $default);
    }
}

if (! function_exists('update_option')) {
    function update_option($option, $value, $autoload = null)
    {
        wp_mock_function('option_' . $option, $value);

        return true;
    }
}

if (! function_exists('delete_option')) {
    function delete_option($option)
    {
        wp_mock_function('option_' . $option, false);

        return true;
    }
}

// WordPress nonce functions
if (! function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1)
    {
        return md5('nonce_' . $action . time());
    }
}

if (! function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1)
    {
        return wp_mock_value('verify_nonce_' . $action, 1);
    }
}

// WordPress transient functions
if (! function_exists('get_transient')) {
    function get_transient($transient)
    {
        return wp_mock_value('transient_' . $transient, false);
    }
}

if (! function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0)
    {
        wp_mock_function('transient_' . $transient, $value);

        return true;
    }
}

if (! function_exists('delete_transient')) {
    function delete_transient($transient)
    {
        wp_mock_function('transient_' . $transient, false);

        return true;
    }
}

// WordPress query functions
if (! function_exists('get_query_var')) {
    function get_query_var($var, $default = '')
    {
        return wp_mock_value('query_var_' . $var, $default);
    }
}
