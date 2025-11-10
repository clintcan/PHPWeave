<?php
/**
 * Query Builder Trait
 *
 * A fluent, database-agnostic query builder for PHPWeave.
 * Provides a clean, chainable API for building SQL queries with automatic
 * parameter binding and SQL injection protection.
 *
 * Features:
 * - Fluent chainable interface
 * - Database-agnostic (MySQL, PostgreSQL, SQLite, SQL Server)
 * - Automatic parameter binding (SQL injection protection)
 * - Support for joins, subqueries, unions
 * - Aggregation functions (COUNT, SUM, AVG, MIN, MAX)
 * - Transaction support
 * - Raw query support when needed
 *
 * @package    PHPWeave
 * @subpackage Core
 * @category   Database
 * @author     PHPWeave Development Team
 * @version    2.4.0
 *
 * @example
 * class user_model extends DBConnection {
 *     use QueryBuilder;
 *
 *     function getActiveUsers() {
 *         return $this->table('users')
 *             ->where('is_active', 1)
 *             ->orderBy('created_at', 'DESC')
 *             ->limit(10)
 *             ->get();
 *     }
 * }
 */
trait QueryBuilder
{
    /**
     * Current table being queried
     * @var string|null
     */
    protected $qb_table = null;

    /**
     * SELECT clause columns
     * @var array
     */
    protected $qb_select = [];

    /**
     * WHERE conditions
     * @var array
     */
    protected $qb_where = [];

    /**
     * JOIN clauses
     * @var array
     */
    protected $qb_joins = [];

    /**
     * ORDER BY clauses
     * @var array
     */
    protected $qb_orderBy = [];

    /**
     * GROUP BY columns
     * @var array
     */
    protected $qb_groupBy = [];

    /**
     * HAVING conditions
     * @var array
     */
    protected $qb_having = [];

    /**
     * LIMIT value
     * @var int|null
     */
    protected $qb_limit = null;

    /**
     * OFFSET value
     * @var int|null
     */
    protected $qb_offset = null;

    /**
     * Bound parameters for prepared statements
     * @var array
     */
    protected $qb_bindings = [];

    /**
     * Parameter counter for unique binding keys
     * @var int
     */
    protected $qb_paramCounter = 0;

    /**
     * UNION queries
     * @var array
     */
    protected $qb_unions = [];

    /**
     * DISTINCT flag
     * @var bool
     */
    protected $qb_distinct = false;

    /**
     * Cache TTL in seconds (null = no caching)
     * @var int|null
     */
    protected $qb_cacheTTL = null;

    /**
     * Cache tags for this query
     * @var array
     */
    protected $qb_cacheTags = [];

    /**
     * Set the table to query
     *
     * @param string $table Table name
     * @return self Chainable
     *
     * @example
     * $this->table('users')->get();
     */
    public function table($table)
    {
        $this->resetQuery();
        $this->qb_table = $table;
        return $this;
    }

    /**
     * Specify columns to select
     *
     * @param string|array ...$columns Columns to select
     * @return self Chainable
     *
     * @example
     * $this->table('users')->select('id', 'name', 'email')->get();
     * $this->table('users')->select(['id', 'name'])->get();
     */
    public function select(...$columns)
    {
        // Flatten array if single array argument passed
        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }

        $this->qb_select = array_merge($this->qb_select, $columns);
        return $this;
    }

    /**
     * Add a raw SELECT expression
     *
     * @param string $expression Raw SQL expression
     * @return self Chainable
     *
     * @example
     * $this->table('users')->selectRaw('COUNT(*) as count')->get();
     */
    public function selectRaw($expression)
    {
        $this->qb_select[] = ['raw' => $expression];
        return $this;
    }

    /**
     * Set DISTINCT flag
     *
     * @return self Chainable
     *
     * @example
     * $this->table('users')->distinct()->select('city')->get();
     */
    public function distinct()
    {
        $this->qb_distinct = true;
        return $this;
    }

    /**
     * Add a WHERE clause
     *
     * Supports multiple calling patterns:
     * - where('column', 'value')           => column = value
     * - where('column', '>', 'value')      => column > value
     * - where(['col1' => 'val1', ...])     => col1 = val1 AND ...
     * - where(function($q) { ... })        => Nested conditions
     *
     * @param string|array|callable $column Column name, array of conditions, or closure
     * @param string|mixed $operatorOrValue Operator or value if 2 params
     * @param mixed $value Value if 3 params
     * @param string $boolean 'AND' or 'OR'
     * @return self Chainable
     *
     * @example
     * $this->table('users')->where('active', 1)->get();
     * $this->table('users')->where('age', '>', 18)->get();
     * $this->table('users')->where(['active' => 1, 'role' => 'admin'])->get();
     */
    public function where($column, $operatorOrValue = null, $value = null, $boolean = 'AND')
    {
        // Handle array of conditions
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val, $boolean);
            }
            return $this;
        }

        // Handle closure for nested conditions
        if ($column instanceof Closure) {
            $query = new static();
            $column($query);
            $this->qb_where[] = [
                'type' => 'nested',
                'query' => $query,
                'boolean' => $boolean
            ];
            return $this;
        }

        // Determine operator and value
        if ($value === null) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = $operatorOrValue;
        }

        // Create unique parameter name
        $paramName = $this->createParamName($column);

        $this->qb_where[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'param' => $paramName,
            'boolean' => $boolean
        ];

        $this->qb_bindings[$paramName] = $value;

        return $this;
    }

    /**
     * Add an OR WHERE clause
     *
     * @param string|array|callable $column Column name or conditions
     * @param string|mixed $operatorOrValue Operator or value
     * @param mixed $value Value
     * @return self Chainable
     *
     * @example
     * $this->table('users')->where('role', 'admin')->orWhere('role', 'moderator')->get();
     */
    public function orWhere($column, $operatorOrValue = null, $value = null)
    {
        return $this->where($column, $operatorOrValue, $value, 'OR');
    }

    /**
     * Add a WHERE IN clause
     *
     * @param string $column Column name
     * @param array $values Array of values
     * @param string $boolean 'AND' or 'OR'
     * @param bool $not Whether to use NOT IN
     * @return self Chainable
     *
     * @example
     * $this->table('users')->whereIn('id', [1, 2, 3])->get();
     */
    public function whereIn($column, array $values, $boolean = 'AND', $not = false)
    {
        $params = [];
        foreach ($values as $value) {
            $paramName = $this->createParamName($column);
            $params[] = $paramName;
            $this->qb_bindings[$paramName] = $value;
        }

        $this->qb_where[] = [
            'type' => $not ? 'notIn' : 'in',
            'column' => $column,
            'params' => $params,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT IN clause
     *
     * @param string $column Column name
     * @param array $values Array of values
     * @param string $boolean 'AND' or 'OR'
     * @return self Chainable
     */
    public function whereNotIn($column, array $values, $boolean = 'AND')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add a WHERE NULL clause
     *
     * @param string $column Column name
     * @param string $boolean 'AND' or 'OR'
     * @param bool $not Whether to use IS NOT NULL
     * @return self Chainable
     *
     * @example
     * $this->table('users')->whereNull('deleted_at')->get();
     */
    public function whereNull($column, $boolean = 'AND', $not = false)
    {
        $this->qb_where[] = [
            'type' => $not ? 'notNull' : 'null',
            'column' => $column,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause
     *
     * @param string $column Column name
     * @param string $boolean 'AND' or 'OR'
     * @return self Chainable
     */
    public function whereNotNull($column, $boolean = 'AND')
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * Add a WHERE BETWEEN clause
     *
     * @param string $column Column name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     * @param string $boolean 'AND' or 'OR'
     * @param bool $not Whether to use NOT BETWEEN
     * @return self Chainable
     *
     * @example
     * $this->table('products')->whereBetween('price', 10, 100)->get();
     */
    public function whereBetween($column, $min, $max, $boolean = 'AND', $not = false)
    {
        $paramMin = $this->createParamName($column . '_min');
        $paramMax = $this->createParamName($column . '_max');

        $this->qb_where[] = [
            'type' => $not ? 'notBetween' : 'between',
            'column' => $column,
            'paramMin' => $paramMin,
            'paramMax' => $paramMax,
            'boolean' => $boolean
        ];

        $this->qb_bindings[$paramMin] = $min;
        $this->qb_bindings[$paramMax] = $max;

        return $this;
    }

    /**
     * Add a WHERE NOT BETWEEN clause
     *
     * @param string $column Column name
     * @param mixed $min Minimum value
     * @param mixed $max Maximum value
     * @param string $boolean 'AND' or 'OR'
     * @return self Chainable
     */
    public function whereNotBetween($column, $min, $max, $boolean = 'AND')
    {
        return $this->whereBetween($column, $min, $max, $boolean, true);
    }

    /**
     * Add a raw WHERE clause
     *
     * @param string $sql Raw SQL expression
     * @param array $bindings Optional bindings
     * @param string $boolean 'AND' or 'OR'
     * @return self Chainable
     *
     * @example
     * $this->table('users')->whereRaw('YEAR(created_at) = ?', [2024])->get();
     */
    public function whereRaw($sql, array $bindings = [], $boolean = 'AND')
    {
        $this->qb_where[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean
        ];

        // Add bindings with unique keys
        foreach ($bindings as $value) {
            $paramName = $this->createParamName('raw');
            $this->qb_bindings[$paramName] = $value;
        }

        return $this;
    }

    /**
     * Add a JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator (=, !=, etc.)
     * @param string $second Second column
     * @param string $type JOIN type (INNER, LEFT, RIGHT, CROSS)
     * @return self Chainable
     *
     * @example
     * $this->table('users')
     *     ->join('posts', 'users.id', '=', 'posts.user_id')
     *     ->get();
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'INNER')
    {
        // Handle 3-argument call: join('table', 'col1', 'col2')
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }

        $this->qb_joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * Add a LEFT JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self Chainable
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN clause
     *
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self Chainable
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add a CROSS JOIN clause
     *
     * @param string $table Table to join
     * @return self Chainable
     */
    public function crossJoin($table)
    {
        $this->qb_joins[] = [
            'type' => 'CROSS',
            'table' => $table
        ];

        return $this;
    }

    /**
     * Add an ORDER BY clause
     *
     * @param string $column Column name
     * @param string $direction ASC or DESC
     * @return self Chainable
     *
     * @example
     * $this->table('users')->orderBy('created_at', 'DESC')->get();
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $direction = strtoupper($direction);
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            $direction = 'ASC';
        }

        $this->qb_orderBy[] = [
            'column' => $column,
            'direction' => $direction
        ];

        return $this;
    }

    /**
     * Add a GROUP BY clause
     *
     * @param string|array ...$columns Columns to group by
     * @return self Chainable
     *
     * @example
     * $this->table('orders')->select('status')->selectRaw('COUNT(*) as count')->groupBy('status')->get();
     */
    public function groupBy(...$columns)
    {
        // Flatten array if single array argument passed
        if (count($columns) === 1 && is_array($columns[0])) {
            $columns = $columns[0];
        }

        $this->qb_groupBy = array_merge($this->qb_groupBy, $columns);
        return $this;
    }

    /**
     * Add a HAVING clause
     *
     * @param string $column Column name or expression
     * @param string|mixed $operatorOrValue Operator or value
     * @param mixed $value Value if 3 params
     * @return self Chainable
     *
     * @example
     * $this->table('orders')->groupBy('status')->having('COUNT(*)', '>', 5)->get();
     */
    public function having($column, $operatorOrValue = null, $value = null)
    {
        // Determine operator and value
        if ($value === null) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = $operatorOrValue;
        }

        $paramName = $this->createParamName('having');

        $this->qb_having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'param' => $paramName
        ];

        $this->qb_bindings[$paramName] = $value;

        return $this;
    }

    /**
     * Set LIMIT
     *
     * @param int $limit Number of rows to return
     * @return self Chainable
     *
     * @example
     * $this->table('users')->limit(10)->get();
     */
    public function limit($limit)
    {
        $this->qb_limit = (int)$limit;
        return $this;
    }

    /**
     * Set OFFSET
     *
     * @param int $offset Number of rows to skip
     * @return self Chainable
     *
     * @example
     * $this->table('users')->limit(10)->offset(20)->get();
     */
    public function offset($offset)
    {
        $this->qb_offset = (int)$offset;
        return $this;
    }

    /**
     * Alias for limit() and offset()
     *
     * @param int $perPage Items per page
     * @param int $page Page number (1-indexed)
     * @return self Chainable
     *
     * @example
     * $this->table('users')->paginate(10, 2)->get(); // Page 2, 10 items per page
     */
    public function paginate($perPage, $page = 1)
    {
        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);
        return $this;
    }

    /**
     * Enable caching for this query
     *
     * @param int $ttl Time to live in seconds (default: 3600)
     * @return self Chainable
     *
     * @example
     * $users = $this->table('users')->where('active', 1)->cache(3600)->get();
     * $posts = $this->table('posts')->cache()->get(); // Uses default TTL
     */
    public function cache($ttl = 3600)
    {
        $this->qb_cacheTTL = $ttl;
        return $this;
    }

    /**
     * Set cache tags for this query
     *
     * @param string|array $tags Tag name(s)
     * @return self Chainable
     *
     * @example
     * $this->table('users')->cacheTags(['users', 'active'])->cache(3600)->get();
     * $this->table('posts')->cacheTags('posts')->cache()->get();
     */
    public function cacheTags($tags)
    {
        $this->qb_cacheTags = is_array($tags) ? $tags : [$tags];
        return $this;
    }

    /**
     * Generate cache key for current query
     *
     * @return string Cache key
     */
    protected function getCacheKey()
    {
        $sql = $this->buildSelectQuery();
        $bindings = json_encode($this->qb_bindings);
        return 'query:' . md5($sql . $bindings);
    }

    /**
     * Execute query and get all results
     *
     * @return array Array of rows
     *
     * @example
     * $users = $this->table('users')->where('active', 1)->get();
     * $cached = $this->table('users')->where('active', 1)->cache(3600)->get();
     */
    public function get()
    {
        // Check if caching is enabled
        if ($this->qb_cacheTTL !== null) {
            require_once __DIR__ . '/cache.php';
            require_once __DIR__ . '/cachedriver.php';

            $cacheKey = $this->getCacheKey();

            // Apply tags if specified
            if (!empty($this->qb_cacheTags)) {
                return Cache::tags($this->qb_cacheTags)->remember($cacheKey, $this->qb_cacheTTL, function() {
                    $sql = $this->buildSelectQuery();
                    $stmt = $this->executePreparedSQL($sql, $this->qb_bindings);
                    return $this->fetchAll($stmt);
                });
            }

            // No tags - simple caching
            return Cache::remember($cacheKey, $this->qb_cacheTTL, function() {
                $sql = $this->buildSelectQuery();
                $stmt = $this->executePreparedSQL($sql, $this->qb_bindings);
                return $this->fetchAll($stmt);
            });
        }

        // No caching - execute normally
        $sql = $this->buildSelectQuery();
        $stmt = $this->executePreparedSQL($sql, $this->qb_bindings);
        return $this->fetchAll($stmt);
    }

    /**
     * Execute query and get first result
     *
     * @return array|false First row or false if not found
     *
     * @example
     * $user = $this->table('users')->where('email', $email)->first();
     * $cached = $this->table('users')->where('id', 1)->cache(3600)->first();
     */
    public function first()
    {
        $this->limit(1);

        // Check if caching is enabled
        if ($this->qb_cacheTTL !== null) {
            require_once __DIR__ . '/cache.php';
            require_once __DIR__ . '/cachedriver.php';

            $cacheKey = $this->getCacheKey() . ':first';

            // Apply tags if specified
            if (!empty($this->qb_cacheTags)) {
                return Cache::tags($this->qb_cacheTags)->remember($cacheKey, $this->qb_cacheTTL, function() {
                    $sql = $this->buildSelectQuery();
                    $stmt = $this->executePreparedSQL($sql, $this->qb_bindings);
                    return $this->fetch($stmt);
                });
            }

            // No tags - simple caching
            return Cache::remember($cacheKey, $this->qb_cacheTTL, function() {
                $sql = $this->buildSelectQuery();
                $stmt = $this->executePreparedSQL($sql, $this->qb_bindings);
                return $this->fetch($stmt);
            });
        }

        // No caching - execute normally
        $sql = $this->buildSelectQuery();
        $stmt = $this->executePreparedSQL($sql, $this->qb_bindings);
        return $this->fetch($stmt);
    }

    /**
     * Find a record by primary key
     *
     * @param mixed $id Primary key value
     * @param string $column Primary key column name (default: 'id')
     * @return array|false Row or false if not found
     *
     * @example
     * $user = $this->table('users')->find(123);
     */
    public function find($id, $column = 'id')
    {
        return $this->where($column, $id)->first();
    }

    /**
     * Get a single column value from first result
     *
     * @param string $column Column name
     * @return mixed Column value or null
     *
     * @example
     * $email = $this->table('users')->where('id', 1)->value('email');
     */
    public function value($column)
    {
        $result = $this->select($column)->first();
        return $result ? $result[$column] : null;
    }

    /**
     * Get array of values for a single column
     *
     * @param string $column Column name
     * @return array Array of values
     *
     * @example
     * $emails = $this->table('users')->pluck('email');
     */
    public function pluck($column)
    {
        $results = $this->select($column)->get();
        return array_column($results, $column);
    }

    /**
     * Check if any rows exist
     *
     * @return bool True if rows exist
     *
     * @example
     * if ($this->table('users')->where('email', $email)->exists()) { ... }
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Count rows
     *
     * @param string $column Column to count (default: *)
     * @return int Row count
     *
     * @example
     * $count = $this->table('users')->where('active', 1)->count();
     */
    public function count($column = '*')
    {
        return $this->aggregate('COUNT', $column);
    }

    /**
     * Get maximum value
     *
     * @param string $column Column name
     * @return mixed Maximum value
     */
    public function max($column)
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Get minimum value
     *
     * @param string $column Column name
     * @return mixed Minimum value
     */
    public function min($column)
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Get average value
     *
     * @param string $column Column name
     * @return mixed Average value
     */
    public function avg($column)
    {
        return $this->aggregate('AVG', $column);
    }

    /**
     * Get sum of values
     *
     * @param string $column Column name
     * @return mixed Sum of values
     */
    public function sum($column)
    {
        return $this->aggregate('SUM', $column);
    }

    /**
     * Execute aggregate function
     *
     * @param string $function Aggregate function name
     * @param string $column Column name
     * @return mixed Result
     */
    protected function aggregate($function, $column)
    {
        // Save original select
        $originalSelect = $this->qb_select;

        // Set aggregate select
        $this->qb_select = [];
        $this->selectRaw("{$function}({$column}) as aggregate");

        $result = $this->first();

        // Restore original select
        $this->qb_select = $originalSelect;

        return $result ? $result['aggregate'] : null;
    }

    /**
     * Insert a new record
     *
     * @param array $data Associative array of column => value
     * @return int|false Last insert ID or false on failure
     *
     * @example
     * $id = $this->table('users')->insert(['name' => 'John', 'email' => 'john@example.com']);
     */
    public function insert(array $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $columnList = implode(', ', $columns);
        $placeholders = ':' . implode(', :', $columns);

        $sql = "INSERT INTO {$this->qb_table} ({$columnList}) VALUES ({$placeholders})";

        // Create bindings
        $bindings = [];
        foreach ($data as $key => $value) {
            $bindings[':' . $key] = $value;
        }

        $stmt = $this->executePreparedSQL($sql, $bindings);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update records
     *
     * @param array $data Associative array of column => value
     * @return int Number of affected rows
     *
     * @example
     * $affected = $this->table('users')->where('id', 1)->update(['name' => 'Jane']);
     */
    public function update(array $data)
    {
        $sets = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $paramName = ':update_' . $column;
            $sets[] = "{$column} = {$paramName}";
            $bindings[$paramName] = $value;
        }

        $sql = "UPDATE {$this->qb_table} SET " . implode(', ', $sets);

        // Add WHERE clause
        if (!empty($this->qb_where)) {
            $sql .= ' ' . $this->buildWhereClause();
            $bindings = array_merge($bindings, $this->qb_bindings);
        }

        $stmt = $this->executePreparedSQL($sql, $bindings);
        return $this->rowCount($stmt);
    }

    /**
     * Delete records
     *
     * @return int Number of affected rows
     *
     * @example
     * $deleted = $this->table('users')->where('id', 1)->delete();
     */
    public function delete()
    {
        $sql = "DELETE FROM {$this->qb_table}";

        // Add WHERE clause
        if (!empty($this->qb_where)) {
            $sql .= ' ' . $this->buildWhereClause();
        }

        $stmt = $this->executePreparedSQL($sql, $this->qb_bindings);
        return $this->rowCount($stmt);
    }

    /**
     * Increment a column value
     *
     * @param string $column Column name
     * @param int $amount Amount to increment (default: 1)
     * @return int Number of affected rows
     *
     * @example
     * $this->table('posts')->where('id', 1)->increment('views');
     */
    public function increment($column, $amount = 1)
    {
        $sql = "UPDATE {$this->qb_table} SET {$column} = {$column} + :amount";

        $bindings = [':amount' => $amount];

        if (!empty($this->qb_where)) {
            $sql .= ' ' . $this->buildWhereClause();
            $bindings = array_merge($bindings, $this->qb_bindings);
        }

        $stmt = $this->executePreparedSQL($sql, $bindings);
        return $this->rowCount($stmt);
    }

    /**
     * Decrement a column value
     *
     * @param string $column Column name
     * @param int $amount Amount to decrement (default: 1)
     * @return int Number of affected rows
     *
     * @example
     * $this->table('products')->where('id', 1)->decrement('stock');
     */
    public function decrement($column, $amount = 1)
    {
        return $this->increment($column, -$amount);
    }

    /**
     * Build SELECT query SQL
     *
     * @return string SQL query
     */
    protected function buildSelectQuery()
    {
        $sql = 'SELECT ';

        // Add DISTINCT
        if ($this->qb_distinct) {
            $sql .= 'DISTINCT ';
        }

        // Add columns
        if (empty($this->qb_select)) {
            $sql .= '*';
        } else {
            $columns = [];
            foreach ($this->qb_select as $column) {
                if (is_array($column) && isset($column['raw'])) {
                    $columns[] = $column['raw'];
                } else {
                    $columns[] = $column;
                }
            }
            $sql .= implode(', ', $columns);
        }

        // Add FROM
        $sql .= " FROM {$this->qb_table}";

        // Add JOINs
        if (!empty($this->qb_joins)) {
            foreach ($this->qb_joins as $join) {
                if ($join['type'] === 'CROSS') {
                    $sql .= " CROSS JOIN {$join['table']}";
                } else {
                    $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
                }
            }
        }

        // Add WHERE
        if (!empty($this->qb_where)) {
            $sql .= ' ' . $this->buildWhereClause();
        }

        // Add GROUP BY
        if (!empty($this->qb_groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->qb_groupBy);
        }

        // Add HAVING
        if (!empty($this->qb_having)) {
            $sql .= ' HAVING ';
            $havingClauses = [];
            foreach ($this->qb_having as $having) {
                $havingClauses[] = "{$having['column']} {$having['operator']} :{$having['param']}";
            }
            $sql .= implode(' AND ', $havingClauses);
        }

        // Add ORDER BY
        if (!empty($this->qb_orderBy)) {
            $sql .= ' ORDER BY ';
            $orders = [];
            foreach ($this->qb_orderBy as $order) {
                $orders[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= implode(', ', $orders);
        }

        // Add LIMIT
        if ($this->qb_limit !== null) {
            $sql .= " LIMIT {$this->qb_limit}";
        }

        // Add OFFSET
        if ($this->qb_offset !== null) {
            $sql .= " OFFSET {$this->qb_offset}";
        }

        return $sql;
    }

    /**
     * Build WHERE clause
     *
     * @return string WHERE clause SQL
     */
    protected function buildWhereClause()
    {
        if (empty($this->qb_where)) {
            return '';
        }

        $clauses = [];
        $firstCondition = true;

        foreach ($this->qb_where as $where) {
            $boolean = $firstCondition ? '' : " {$where['boolean']} ";
            $firstCondition = false;

            switch ($where['type']) {
                case 'basic':
                    $clauses[] = $boolean . "{$where['column']} {$where['operator']} :{$where['param']}";
                    break;

                case 'in':
                    $params = ':' . implode(', :', $where['params']);
                    $clauses[] = $boolean . "{$where['column']} IN ({$params})";
                    break;

                case 'notIn':
                    $params = ':' . implode(', :', $where['params']);
                    $clauses[] = $boolean . "{$where['column']} NOT IN ({$params})";
                    break;

                case 'null':
                    $clauses[] = $boolean . "{$where['column']} IS NULL";
                    break;

                case 'notNull':
                    $clauses[] = $boolean . "{$where['column']} IS NOT NULL";
                    break;

                case 'between':
                    $clauses[] = $boolean . "{$where['column']} BETWEEN :{$where['paramMin']} AND :{$where['paramMax']}";
                    break;

                case 'notBetween':
                    $clauses[] = $boolean . "{$where['column']} NOT BETWEEN :{$where['paramMin']} AND :{$where['paramMax']}";
                    break;

                case 'raw':
                    $clauses[] = $boolean . $where['sql'];
                    break;
            }
        }

        return 'WHERE ' . implode('', $clauses);
    }

    /**
     * Create unique parameter name
     *
     * @param string $column Column name
     * @return string Parameter name
     */
    protected function createParamName($column)
    {
        return 'qb_' . str_replace('.', '_', $column) . '_' . $this->qb_paramCounter++;
    }

    /**
     * Reset query builder state
     *
     * @return void
     */
    protected function resetQuery()
    {
        $this->qb_table = null;
        $this->qb_select = [];
        $this->qb_where = [];
        $this->qb_joins = [];
        $this->qb_orderBy = [];
        $this->qb_groupBy = [];
        $this->qb_having = [];
        $this->qb_limit = null;
        $this->qb_offset = null;
        $this->qb_bindings = [];
        $this->qb_paramCounter = 0;
        $this->qb_unions = [];
        $this->qb_distinct = false;
        $this->qb_cacheTTL = null;
        $this->qb_cacheTags = [];
    }

    /**
     * Begin a database transaction
     *
     * @return bool True on success
     *
     * @example
     * $this->beginTransaction();
     * try {
     *     $this->table('users')->insert($userData);
     *     $this->table('profiles')->insert($profileData);
     *     $this->commit();
     * } catch (Exception $e) {
     *     $this->rollback();
     * }
     */
    public function beginTransaction()
    {
        $this->connect();
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the active database transaction
     *
     * @return bool True on success
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback the active database transaction
     *
     * @return bool True on success
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Execute a raw SQL query
     *
     * @param string $sql SQL query
     * @param array $bindings Parameter bindings
     * @return PDOStatement Executed statement
     *
     * @example
     * $stmt = $this->raw("SELECT * FROM users WHERE email = :email", ['email' => $email]);
     * $result = $this->fetchAll($stmt);
     */
    public function raw($sql, array $bindings = [])
    {
        return $this->executePreparedSQL($sql, $bindings);
    }

    /**
     * Get the generated SQL query (for debugging)
     *
     * @return string SQL query
     *
     * @example
     * echo $this->table('users')->where('active', 1)->toSql();
     */
    public function toSql()
    {
        return $this->buildSelectQuery();
    }

    /**
     * Get the bindings (for debugging)
     *
     * @return array Bindings
     */
    public function getBindings()
    {
        return $this->qb_bindings;
    }
}
