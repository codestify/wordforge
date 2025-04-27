<?php

/**
 * WordPress Core Classes Mock
 *
 * This file provides mock implementations of commonly used WordPress classes
 * for testing purposes.
 */

if (!class_exists('WP_Error')) {
    /**
     * WordPress Error class
     */
    class WP_Error {
        /**
         * Error codes
         *
         * @var array
         */
        protected $errors = [];

        /**
         * Error data
         *
         * @var array
         */
        protected $error_data = [];

        /**
         * Constructor
         *
         * @param string|int $code Error code
         * @param string $message Error message
         * @param mixed $data Optional data
         */
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->add($code, $message, $data);
            }
        }

        /**
         * Add an error
         *
         * @param string|int $code Error code
         * @param string $message Error message
         * @param mixed $data Optional data
         * @return void
         */
        public function add($code, $message, $data = '') {
            $this->errors[$code][] = $message;

            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }

        /**
         * Get error codes
         *
         * @return array
         */
        public function get_error_codes() {
            return array_keys($this->errors);
        }

        /**
         * Get error messages
         *
         * @param string|int $code Optional error code
         * @return array|string
         */
        public function get_error_messages($code = '') {
            if (empty($code)) {
                $all_messages = [];
                foreach ($this->errors as $messages) {
                    $all_messages = array_merge($all_messages, $messages);
                }
                return $all_messages;
            }

            return $this->errors[$code] ?? [];
        }

        /**
         * Get a single error message
         *
         * @param string|int $code Error code
         * @return string
         */
        public function get_error_message($code = '') {
            if (empty($code)) {
                $codes = $this->get_error_codes();
                if (empty($codes)) {
                    return '';
                }
                $code = $codes[0];
            }

            $messages = $this->get_error_messages($code);
            if (empty($messages)) {
                return '';
            }

            return $messages[0];
        }

        /**
         * Get error data
         *
         * @param string|int $code Error code
         * @return mixed
         */
        public function get_error_data($code = '') {
            if (empty($code)) {
                $codes = $this->get_error_codes();
                if (empty($codes)) {
                    return null;
                }
                $code = $codes[0];
            }

            return $this->error_data[$code] ?? null;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    /**
     * WordPress REST Request class
     */
    class WP_REST_Request {
        /**
         * Request method
         *
         * @var string
         */
        protected $method = 'GET';

        /**
         * Request parameters
         *
         * @var array
         */
        protected $params = [];

        /**
         * URL parameters
         *
         * @var array
         */
        protected $url_params = [];

        /**
         * Query parameters
         *
         * @var array
         */
        protected $query_params = [];

        /**
         * Body parameters
         *
         * @var array
         */
        protected $body_params = [];

        /**
         * File parameters
         *
         * @var array
         */
        protected $file_params = [];

        /**
         * Headers
         *
         * @var array
         */
        protected $headers = [];

        /**
         * Route
         *
         * @var string
         */
        protected $route = '';

        /**
         * Constructor
         *
         * @param string $method Request method
         * @param string $route Request route
         * @param array $params Request parameters
         */
        public function __construct($method = 'GET', $route = '', $params = []) {
            $this->method = $method;
            $this->route = $route;
            $this->params = $params;
        }

        /**
         * Get all parameters
         *
         * @return array
         */
        public function get_params() {
            return array_merge(
                $this->url_params,
                $this->query_params,
                $this->body_params
            );
        }

        /**
         * Get URL parameters
         *
         * @return array
         */
        public function get_url_params() {
            return $this->url_params;
        }

        /**
         * Get query parameters
         *
         * @return array
         */
        public function get_query_params() {
            return $this->query_params;
        }

        /**
         * Get body parameters
         *
         * @return array
         */
        public function get_body_params() {
            return $this->body_params;
        }

        /**
         * Get JSON parameters
         *
         * @return array
         */
        public function get_json_params() {
            // If body_params contains JSON data, return that
            // Otherwise, try to parse the body content as JSON
            if (empty($this->body_params) && !empty($this->get_body())) {
                $json_params = json_decode($this->get_body(), true);
                if (is_array($json_params)) {
                    return $json_params;
                }
            }

            // If no parseable JSON found, return empty array
            return !empty($this->body_params) ? $this->body_params : [];
        }

        /**
         * Get file parameters
         *
         * @return array
         */
        public function get_file_params() {
            return $this->file_params;
        }

        /**
         * Get headers
         *
         * @return array
         */
        public function get_headers() {
            return $this->headers;
        }

        /**
         * Get method
         *
         * @return string
         */
        public function get_method() {
            return $this->method;
        }

        /**
         * Get route
         *
         * @return string
         */
        public function get_route() {
            return $this->route;
        }

        /**
         * Get body
         *
         * @return string
         */
        public function get_body() {
            return json_encode($this->body_params);
        }

        /**
         * Set parameter
         *
         * @param string $key Parameter key
         * @param mixed $value Parameter value
         * @return void
         */
        public function set_param($key, $value) {
            $this->params[$key] = $value;
        }

        /**
         * Set URL parameter
         *
         * @param string $key Parameter key
         * @param mixed $value Parameter value
         * @return void
         */
        public function set_url_param($key, $value) {
            $this->url_params[$key] = $value;
        }

        /**
         * Set query parameter
         *
         * @param string $key Parameter key
         * @param mixed $value Parameter value
         * @return void
         */
        public function set_query_param($key, $value) {
            $this->query_params[$key] = $value;
        }

        /**
         * Set body parameter
         *
         * @param string $key Parameter key
         * @param mixed $value Parameter value
         * @return void
         */
        public function set_body_param($key, $value) {
            $this->body_params[$key] = $value;
        }

        /**
         * Set file parameter
         *
         * @param string $key Parameter key
         * @param mixed $value Parameter value
         * @return void
         */
        public function set_file_param($key, $value) {
            $this->file_params[$key] = $value;
        }

        /**
         * Set header
         *
         * @param string $key Header key
         * @param mixed $value Header value
         * @return void
         */
        public function set_header($key, $value) {
            $this->headers[strtolower($key)] = [$value];
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    /**
     * WordPress REST Response class
     */
    class WP_REST_Response {
        /**
         * Response data
         *
         * @var mixed
         */
        protected $data;

        /**
         * Response status
         *
         * @var int
         */
        protected $status = 200;

        /**
         * Response headers
         *
         * @var array
         */
        protected $headers = [];

        /**
         * Constructor
         *
         * @param mixed $data Response data
         * @param int $status Response status
         * @param array $headers Response headers
         */
        public function __construct($data = null, $status = 200, $headers = []) {
            $this->data = $data;
            $this->status = $status;
            $this->headers = $headers;
        }

        /**
         * Get response data
         *
         * @return mixed
         */
        public function get_data() {
            return $this->data;
        }

        /**
         * Set response data
         *
         * @param mixed $data
         * @return $this
         */
        public function set_data($data) {
            $this->data = $data;
            return $this;
        }

        /**
         * Get response status
         *
         * @return int
         */
        public function get_status() {
            return $this->status;
        }

        /**
         * Set response status
         *
         * @param int $status
         * @return $this
         */
        public function set_status($status) {
            $this->status = $status;
            return $this;
        }

        /**
         * Get response headers
         *
         * @return array
         */
        public function get_headers() {
            return $this->headers;
        }

        /**
         * Set response headers
         *
         * @param array $headers
         * @return $this
         */
        public function set_headers($headers) {
            $this->headers = $headers;
            return $this;
        }

        /**
         * Add/set a response header
         *
         * @param string $key
         * @param string $value
         * @return $this
         */
        public function header($key, $value) {
            $this->headers[$key] = $value;
            return $this;
        }
    }
}

if (!class_exists('WP_REST_Server')) {
    /**
     * WordPress REST Server class
     */
    class WP_REST_Server {
        // Simple stub implementation
    }
}

if (!class_exists('WP_DB')) {
    /**
     * Mock WordPress DB class
     */
    class WP_DB {
        /**
         * Table prefix
         *
         * @var string
         */
        public $prefix = 'wp_';

        /**
         * Last insert ID
         *
         * @var int
         */
        public $insert_id = 0;

        /**
         * Last query
         *
         * @var string
         */
        public $last_query = '';

        /**
         * Last error
         *
         * @var string
         */
        public $last_error = '';

        /**
         * Prepare SQL query with placeholders
         *
         * @param string $query Query with placeholders
         * @param array|mixed $args Values to replace placeholders
         * @return string Prepared query
         */
        public function prepare($query, $args = []) {
            $this->last_query = $query;

            // Simple implementation to replace placeholders
            if (empty($args)) {
                return $query;
            }

            if (!is_array($args)) {
                $args = [$args];
            }

            $i = 0;

            return preg_replace_callback('/%[sdf]/', function($matches) use (&$i, $args) {
                if (!isset($args[$i])) {
                    return 'NULL';
                }

                $value = $args[$i++];
                if (is_string($value)) {
                    return "\"$value\"";
                }
                return $value;
            }, $query);
        }

        /**
         * Insert a row
         *
         * @param string $table Table name
         * @param array $data Data to insert
         * @param array|null $format Formats for values
         * @return bool|int False on error, number of rows inserted on success
         */
        public function insert($table, $data, $format = null) {
            $this->insert_id = mt_rand(1, 1000);
            return true;
        }

        /**
         * Update a row
         *
         * @param string $table Table name
         * @param array $data Data to update
         * @param array $where Where conditions
         * @param array|null $format Formats for values
         * @param array|null $where_format Formats for where values
         * @return bool|int False on error, number of rows updated on success
         */
        public function update($table, $data, $where, $format = null, $where_format = null) {
            return 1;
        }

        /**
         * Delete a row
         *
         * @param string $table Table name
         * @param array $where Where conditions
         * @param array|null $where_format Formats for where values
         * @return bool|int False on error, number of rows deleted on success
         */
        public function delete($table, $where, $where_format = null) {
            return 1;
        }

        /**
         * Perform a query and get results
         *
         * @param string $query SQL query
         * @param string $output Output format
         * @return array Results
         */
        public function get_results($query, $output = OBJECT) {
            $this->last_query = $query;
            return [];
        }

        /**
         * Get a single row
         *
         * @param string $query SQL query
         * @param string $output Output format
         * @param int $row_offset Row offset
         * @return object|null Row or null
         */
        public function get_row($query = null, $output = OBJECT, $row_offset = 0) {
            $this->last_query = $query;
            return null;
        }

        /**
         * Get a single column
         *
         * @param string $query SQL query
         * @param int $x Column index
         * @return array Column values
         */
        public function get_col($query = null, $x = 0) {
            $this->last_query = $query;
            return [];
        }

        /**
         * Get a single variable
         *
         * @param string $query SQL query
         * @param int $x Column index
         * @param int $y Row index
         * @return mixed|null Variable or null
         */
        public function get_var($query = null, $x = 0, $y = 0) {
            $this->last_query = $query;
            return null;
        }

        /**
         * Execute a query
         *
         * @param string $query SQL query
         * @return bool|int|resource False on error, affected rows on success
         */
        public function query($query) {
            $this->last_query = $query;
            return 1;
        }

        /**
         * Replace a row
         *
         * @param string $table Table name
         * @param array $data Data to replace
         * @param array|null $format Formats for values
         * @return bool|int False on error, affected rows on success
         */
        public function replace($table, $data, $format = null) {
            return 1;
        }
    }
}