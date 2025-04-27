<?php

namespace WordForge\Database;

class QueryBuilder
{
    /**
     * The WordPress database instance
     * @var \wpdb
     */
    protected $wpdb;

    /**
     * The table name
     * @var string
     */
    protected $table;

    /**
     * The columns to select
     * @var array
     */
    protected $columns = ['*'];

    /**
     * The where constraints
     * @var array
     */
    protected $wheres = [];

    /**
     * The having constraints
     * @var array
     */
    protected $havings = [];

    /**
     * The join statements
     * @var array
     */
    protected $joins = [];

    /**
     * The orderings for the query
     * @var array
     */
    protected $orders = [];

    /**
     * The groupings for the query
     * @var array
     */
    protected $groups = [];

    /**
     * The maximum number of records to return
     * @var int
     */
    protected $limit;

    /**
     * The number of records to skip
     * @var int
     */
    protected $offset;

    /**
     * The where parameters for prepared statements
     * @var array
     */
    protected $bindings = [
        'where' => [],
        'having' => [],
        'join' => [],
    ];

    /**
     * Whether to include the prefix in table names
     * @var bool
     */
    protected $autoPrefix = true;

    /**
     * Constructor
     *
     * @param string $table The table name (without prefix)
     * @param bool $autoPrefix Whether to automatically add the WordPress table prefix
     */
    public function __construct($table, $autoPrefix = true)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->autoPrefix = $autoPrefix;
        $this->table = $autoPrefix ? $wpdb->prefix . $table : $table;
    }

    /**
     * Create a new query builder instance
     *
     * @param string $table The table name (without prefix)
     * @return static
     */
    public static function table($table, $autoPrefix = true)
    {
        return new static($table, $autoPrefix);
    }

    /**
     * Set the columns to be selected
     *
     * @param array|string $columns The columns to select
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return static
     */
    public function newQuery()
    {
        return new static($this->table, $this->autoPrefix);
    }

    /**
     * Add a basic where clause to the query
     *
     * @param string|array|\Closure $column Column name or array of conditions
     * @param mixed $operator Operator or value
     * @param mixed $value Value (if operator provided)
     * @param string $boolean The boolean operator (AND/OR)
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        // Handle array of where clauses
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, '=', $value);
            }
            return $this;
        }

        // Handle Closure for nested where
        if ($column instanceof \Closure) {
            return $this->whereNested($column, $boolean);
        }

        // If only two arguments are provided, assume equals
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        if ($value === null) {
            return $this->whereNull($column, $boolean);
        }

        $type = 'basic';

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
        $this->bindings['where'][] = $value;

        return $this;
    }

    /**
     * Add an OR where clause
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a where in clause
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereIn($column, array $values, $boolean = 'AND', $not = false)
    {
        $type = $not ? 'notIn' : 'in';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        foreach ($values as $value) {
            $this->bindings['where'][] = $value;
        }

        return $this;
    }

    /**
     * Add a where not in clause
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotIn($column, array $values, $boolean = 'AND')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an or where in clause
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function orWhereIn($column, array $values)
    {
        return $this->whereIn($column, $values, 'OR');
    }

    /**
     * Add a where null clause
     *
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'AND', $not = false)
    {
        $type = $not ? 'notNull' : 'null';

        $this->wheres[] = compact('type', 'column', 'boolean');

        return $this;
    }

    /**
     * Add a where not null clause
     *
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'AND')
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * Add a where between clause
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'AND', $not = false)
    {
        $type = $not ? 'notBetween' : 'between';

        $this->wheres[] = compact('type', 'column', 'values', 'boolean');

        $this->bindings['where'][] = $values[0];
        $this->bindings['where'][] = $values[1];

        return $this;
    }

    /**
     * Add a where not between clause
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function whereNotBetween($column, array $values, $boolean = 'AND')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add a where like clause
     *
     * @param string $column
     * @param string $value
     * @param string $boolean
     * @return $this
     */
    public function whereLike($column, $value, $boolean = 'AND')
    {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    /**
     * Add a nested where statement
     *
     * @param \Closure $callback
     * @param string $boolean
     * @return $this
     */
    public function whereNested(\Closure $callback, $boolean = 'AND')
    {
        $query = new static($this->table, false); // Don't auto-prefix for nested queries

        $callback($query);

        if (count($query->wheres)) {
            $type = 'nested';
            $this->wheres[] = compact('type', 'query', 'boolean');
            $this->bindings['where'] = array_merge($this->bindings['where'], $query->bindings['where']);
        }

        return $this;
    }

    /**
     * Add a raw where clause
     *
     * @param string $sql
     * @param array $bindings
     * @param string $boolean
     * @return $this
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'AND')
    {
        $type = 'raw';

        $this->wheres[] = compact('type', 'sql', 'boolean');

        $this->bindings['where'] = array_merge($this->bindings['where'], $bindings);

        return $this;
    }

    /**
     * Add a join clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner')
    {
        // If the second and operator are null, assume a raw join
        if ($operator === null && $second === null) {
            $this->joins[] = [
                'type' => $type,
                'table' => $this->autoPrefix ? $this->wpdb->prefix . $table : $table,
                'on' => $first
            ];
            return $this;
        }

        $join = [
            'type' => $type,
            'table' => $this->autoPrefix ? $this->wpdb->prefix . $table : $table,
            'on' => compact('first', 'operator', 'second')
        ];

        $this->joins[] = $join;

        if (!in_array($operator, ['=', '<', '>', '<=', '>=', '<>', '!='])) {
            $this->bindings['join'][] = $second;
        }

        return $this;
    }

    /**
     * Add a left join clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Add a right join clause
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Add an order by clause
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction
        ];

        return $this;
    }

    /**
     * Add a raw order by clause
     *
     * @param string $sql
     * @return $this
     */
    public function orderByRaw($sql)
    {
        $this->orders[] = ['type' => 'raw', 'sql' => $sql];

        return $this;
    }

    /**
     * Add a descending order by clause
     *
     * @param string $column
     * @return $this
     */
    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add a group by clause
     *
     * @param array|string $groups
     * @return $this
     */
    public function groupBy($groups)
    {
        $this->groups = is_array($groups) ? $groups : func_get_args();

        return $this;
    }

    /**
     * Add a having clause
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function having($column, $operator = null, $value = null, $boolean = 'AND')
    {
        // If only two arguments, assume equals
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = compact('column', 'operator', 'value', 'boolean');

        $this->bindings['having'][] = $value;

        return $this;
    }

    /**
     * Add a limit clause
     *
     * @param int $value
     * @return $this
     */
    public function limit($value)
    {
        $this->limit = max(0, (int) $value);

        return $this;
    }

    /**
     * Add an offset clause
     *
     * @param int $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = max(0, (int) $value);

        return $this;
    }

    /**
     * Take a certain number of results
     * (alias for limit)
     *
     * @param int $value
     * @return $this
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Skip a certain number of results
     * (alias for offset)
     *
     * @param int $value
     * @return $this
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Paginate results
     *
     * @param int $perPage
     * @param int $page
     * @return $this
     */
    public function paginate($perPage = 15, $page = null)
    {
        $page = $page ?: max(1, \get_query_var('paged', 1));

        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);

        return $this;
    }

    /**
     * Get a single record
     *
     * @param array|string $columns
     * @return object|null
     */
    public function first($columns = ['*'])
    {
        if (!empty($columns) && $columns !== ['*']) {
            $this->select($columns);
        }

        $this->limit(1);

        $results = $this->get();

        return !empty($results) ? $results[0] : null;
    }

    /**
     * Find a record by its primary key
     *
     * @param mixed $id
     * @param array|string $columns
     * @return object|null
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Execute a get query
     *
     * @param array|string $columns
     * @return array
     */
    public function get($columns = ['*'])
    {
        if (!empty($columns) && $columns !== ['*']) {
            $this->select($columns);
        }

        $sql = $this->toSql();
        $bindings = $this->getBindings();

        if (empty($bindings)) {
            $results = $this->wpdb->get_results($sql);
        } else {
            $prepared = $this->wpdb->prepare($sql, $bindings);
            $results = $this->wpdb->get_results($prepared);
        }

        return $results ?: [];
    }

    /**
     * Execute a get query and return the results as an array of key-value pairs
     *
     * @param string $column
     * @param string $key
     * @return array
     */
    public function pluck($column, $key = null)
    {
        $results = $this->get(is_null($key) ? [$column] : [$column, $key]);

        $values = [];

        if (empty($results)) {
            return [];
        }

        if (is_null($key)) {
            foreach ($results as $row) {
                $values[] = $row->$column;
            }
        } else {
            foreach ($results as $row) {
                $values[$row->$key] = $row->$column;
            }
        }

        return $values;
    }

    /**
     * Execute an aggregate function query
     *
     * @param string $function
     * @param array $columns
     * @return mixed
     */
    protected function aggregate($function, $columns = ['*'])
    {
        $this->select([$function . '(' . implode(', ', (array) $columns) . ') as aggregate']);

        $result = $this->first();

        if (!$result) {
            return 0;
        }

        return (int) $result->aggregate;
    }

    /**
     * Count the number of records
     *
     * @param string $column
     * @return int
     */
    public function count($column = '*')
    {
        return $this->aggregate('COUNT', [$column]);
    }

    /**
     * Get the maximum value of a column
     *
     * @param string $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate('MAX', [$column]);
    }

    /**
     * Get the minimum value of a column
     *
     * @param string $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate('MIN', [$column]);
    }

    /**
     * Get the average value of a column
     *
     * @param string $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate('AVG', [$column]);
    }

    /**
     * Get the sum of a column
     *
     * @param string $column
     * @return mixed
     */
    public function sum($column)
    {
        return $this->aggregate('SUM', [$column]);
    }

    /**
     * Insert a record
     *
     * @param array $values
     * @return int|false
     */
    public function insert(array $values)
    {
        $result = $this->wpdb->insert($this->table, $values);

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Insert multiple records
     *
     * @param array $values
     * @return int|false
     */
    public function insertMany(array $values)
    {
        if (empty($values)) {
            return false;
        }

        // Get the columns from the first row
        $columns = array_keys($values[0]);

        // Build query
        $query = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES ";

        $rows = [];
        $bindings = [];

        foreach ($values as $row) {
            $placeholders = [];

            foreach ($columns as $column) {
                $placeholders[] = '%s';
                $bindings[] = $row[$column] ?? null;
            }

            $rows[] = '(' . implode(', ', $placeholders) . ')';
        }

        $query .= implode(', ', $rows);

        $prepared = $this->wpdb->prepare($query, $bindings);
        $result = $this->wpdb->query($prepared);

        return $result ? $result : false;
    }

    /**
     * Update records
     *
     * @param array $values
     * @return int|false
     */
    public function update(array $values)
    {
        // If no wheres, don't allow updates
        if (empty($this->wheres)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET ";

        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $sets[] = "{$column} = %s";
            $bindings[] = $value;
        }

        $sql .= implode(', ', $sets);

        $sql .= $this->compileWheres();

        $bindings = array_merge($bindings, $this->bindings['where']);

        $prepared = $this->wpdb->prepare($sql, $bindings);
        $result = $this->wpdb->query($prepared);

        return $result !== false ? $result : false;
    }

    /**
     * Delete records
     *
     * @return int|false
     */
    public function delete()
    {
        // If no wheres, don't allow deletes
        if (empty($this->wheres)) {
            return false;
        }

        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->compileWheres();

        $bindings = $this->bindings['where'];

        if (empty($bindings)) {
            $result = $this->wpdb->query($sql);
        } else {
            $prepared = $this->wpdb->prepare($sql, $bindings);
            $result = $this->wpdb->query($prepared);
        }

        return $result !== false ? $result : false;
    }

    /**
     * Execute a raw query
     *
     * @param string $query
     * @param array $bindings
     * @return array|null
     */
    public function raw($query, $bindings = [])
    {
        if (empty($bindings)) {
            return $this->wpdb->get_results($query);
        }

        $prepared = $this->wpdb->prepare($query, $bindings);
        return $this->wpdb->get_results($prepared);
    }

    public function beginTransaction()
    {
        return $this->wpdb->query('START TRANSACTION');
    }

    public function commit()
    {
        return $this->wpdb->query('COMMIT');
    }

    public function rollback()
    {
        return $this->wpdb->query('ROLLBACK');
    }

    /**
     * Execute a callback within a transaction
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get SQL representation of the query
     *
     * @return string
     */
    public function toSql()
    {
        $sql = $this->compileSelect()
               . $this->compileFrom()
               . $this->compileJoins()
               . $this->compileWheres()
               . $this->compileGroups()
               . $this->compileHavings()
               . $this->compileOrders()
               . $this->compileLimit()
               . $this->compileOffset();

        return $sql;
    }

    /**
     * Get the query bindings
     *
     * @return array
     */
    public function getBindings()
    {
        return array_merge(
            $this->bindings['join'],
            $this->bindings['where'],
            $this->bindings['having']
        );
    }

    /**
     * Compile the select clause
     *
     * @return string
     */
    protected function compileSelect()
    {
        if (empty($this->columns)) {
            $this->columns = ['*'];
        }

        return 'SELECT ' . implode(', ', $this->columns);
    }

    /**
     * Compile the from clause
     *
     * @return string
     */
    protected function compileFrom()
    {
        return ' FROM ' . $this->table;
    }

    /**
     * Compile the join clauses
     *
     * @return string
     */
    protected function compileJoins()
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';

        foreach ($this->joins as $join) {
            $type = strtoupper($join['type']);
            $table = $join['table'];

            $sql .= " {$type} JOIN {$table}";

            if (isset($join['on'])) {
                if (is_string($join['on'])) {
                    $sql .= " ON {$join['on']}";
                } else {
                    $sql .= " ON {$join['on']['first']} {$join['on']['operator']} {$join['on']['second']}";
                }
            }
        }

        return $sql;
    }

    /**
     * Compile the where clauses
     *
     * @return string
     */
    protected function compileWheres()
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = ' WHERE ';
        $first = true;

        foreach ($this->wheres as $where) {
            if (!$first) {
                $sql .= " {$where['boolean']} ";
            } else {
                $first = false;
            }

            $type = $where['type'];

            switch ($type) {
                case 'basic':
                    $sql .= "{$where['column']} {$where['operator']} %s";
                    break;
                case 'in':
                    $placeholders = array_fill(0, count($where['values']), '%s');
                    $sql .= "{$where['column']} IN (" . implode(', ', $placeholders) . ")";
                    break;
                case 'notIn':
                    $placeholders = array_fill(0, count($where['values']), '%s');
                    $sql .= "{$where['column']} NOT IN (" . implode(', ', $placeholders) . ")";
                    break;
                case 'null':
                    $sql .= "{$where['column']} IS NULL";
                    break;
                case 'notNull':
                    $sql .= "{$where['column']} IS NOT NULL";
                    break;
                case 'between':
                    $sql .= "{$where['column']} BETWEEN %s AND %s";
                    break;
                case 'notBetween':
                    $sql .= "{$where['column']} NOT BETWEEN %s AND %s";
                    break;
                case 'nested':
                    $nested = $where['query'];
                    $nestedSql = $nested->compileWheres();
                    // Make sure to properly handle the nested SQL removing the 'WHERE ' part
                    $sql .= '(' . substr($nestedSql, 7) . ')';
                    break;
                case 'raw':
                    $sql .= $where['sql'];
                    break;
            }
        }

        return $sql;
    }

    /**
     * Compile the group by clauses
     *
     * @return string
     */
    protected function compileGroups()
    {
        if (empty($this->groups)) {
            return '';
        }

        return ' GROUP BY ' . implode(', ', $this->groups);
    }

    /**
     * Compile the having clauses
     *
     * @return string
     */
    protected function compileHavings()
    {
        if (empty($this->havings)) {
            return '';
        }

        $sql = ' HAVING ';
        $first = true;

        foreach ($this->havings as $having) {
            if (!$first) {
                $sql .= " {$having['boolean']} ";
            } else {
                $first = false;
            }

            $sql .= "{$having['column']} {$having['operator']} %s";
        }

        return $sql;
    }

    /**
     * Compile the order by clauses
     *
     * @return string
     */
    protected function compileOrders()
    {
        if (empty($this->orders)) {
            return '';
        }

        $orders = [];

        foreach ($this->orders as $order) {
            if (isset($order['type']) && $order['type'] === 'raw') {
                $orders[] = $order['sql'];
            } else {
                $orders[] = "{$order['column']} {$order['direction']}";
            }
        }

        return ' ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Compile the limit clause
     *
     * @return string
     */
    protected function compileLimit()
    {
        if (is_null($this->limit)) {
            return '';
        }

        return ' LIMIT ' . (int) $this->limit;
    }

    /**
     * Compile the offset clause
     *
     * @return string
     */
    protected function compileOffset()
    {
        if (is_null($this->offset)) {
            return '';
        }

        return ' OFFSET ' . (int) $this->offset;
    }
}
