<?php
/**
 * Query Builder Test Suite
 *
 * Comprehensive tests for the QueryBuilder trait.
 * Tests all query building methods, SQL generation, and database operations.
 *
 * @package    PHPWeave
 * @subpackage Tests
 * @category   Tests
 * @author     PHPWeave Development Team
 * @version    2.4.0
 *
 * Usage: php tests/test_query_builder.php
 */

// Set up paths
define('PHPWEAVE_ROOT', dirname(__DIR__));
require_once PHPWEAVE_ROOT . '/coreapp/dbconnection.php';
require_once PHPWEAVE_ROOT . '/coreapp/querybuilder.php';

// Mock environment variables for testing
$GLOBALS['configs'] = [
    'DBDRIVER' => 'pdo_sqlite',
    'DBNAME' => ':memory:',
    'DBHOST' => '',
    'DBUSER' => '',
    'DBPASSWORD' => '',
    'DBCHARSET' => 'utf8',
    'DBPORT' => 0,
    'DEBUG' => 1
];

/**
 * Test Model using QueryBuilder
 */
class test_model extends DBConnection
{
    use QueryBuilder;
}

/**
 * Test runner
 */
class QueryBuilderTest
{
    private $model;
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function __construct()
    {
        $this->model = new test_model();
        $this->setupDatabase();
    }

    /**
     * Set up in-memory SQLite database with test data
     */
    private function setupDatabase()
    {
        // Create users table
        $sql = "
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                age INTEGER,
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->model->executePreparedSQL($sql);

        // Create posts table
        $sql = "
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                title TEXT NOT NULL,
                content TEXT,
                views INTEGER DEFAULT 0,
                published BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->model->executePreparedSQL($sql);

        // Insert test users
        $users = [
            ['Alice', 'alice@example.com', 25, 'active'],
            ['Bob', 'bob@example.com', 30, 'active'],
            ['Charlie', 'charlie@example.com', 35, 'inactive'],
            ['David', 'david@example.com', 28, 'active'],
            ['Eve', 'eve@example.com', 32, 'active']
        ];

        foreach ($users as $user) {
            $sql = "INSERT INTO users (name, email, age, status) VALUES (?, ?, ?, ?)";
            $this->model->executePreparedSQL($sql, $user);
        }

        // Insert test posts
        $posts = [
            [1, 'First Post', 'Content 1', 100, 1],
            [1, 'Second Post', 'Content 2', 50, 1],
            [2, 'Bob Post', 'Content 3', 75, 1],
            [3, 'Charlie Post', 'Content 4', 0, 0],
            [4, 'David Post', 'Content 5', 200, 1]
        ];

        foreach ($posts as $post) {
            $sql = "INSERT INTO posts (user_id, title, content, views, published) VALUES (?, ?, ?, ?, ?)";
            $this->model->executePreparedSQL($sql, $post);
        }
    }

    /**
     * Assert test result
     */
    private function assert($condition, $testName, $message = '')
    {
        if ($condition) {
            $this->passed++;
            $this->tests[] = ['name' => $testName, 'status' => 'PASS'];
            echo "✓ PASS: {$testName}\n";
        } else {
            $this->failed++;
            $this->tests[] = ['name' => $testName, 'status' => 'FAIL', 'message' => $message];
            echo "✗ FAIL: {$testName}" . ($message ? " - {$message}" : "") . "\n";
        }
    }

    /**
     * Test basic table() and get()
     */
    public function testBasicSelect()
    {
        $users = $this->model->table('users')->get();
        $this->assert(count($users) === 5, 'Basic SELECT - get all users');
        $this->assert(isset($users[0]['name']), 'Basic SELECT - row has name column');
    }

    /**
     * Test select() with specific columns
     */
    public function testSelectColumns()
    {
        $users = $this->model->table('users')->select('id', 'name')->get();
        $this->assert(isset($users[0]['id']) && isset($users[0]['name']), 'SELECT columns - id and name present');
        $this->assert(!isset($users[0]['email']), 'SELECT columns - email not present');
    }

    /**
     * Test where() clause
     */
    public function testWhere()
    {
        $users = $this->model->table('users')->where('name', 'Alice')->get();
        $this->assert(count($users) === 1, 'WHERE clause - finds Alice');
        $this->assert($users[0]['email'] === 'alice@example.com', 'WHERE clause - correct user');
    }

    /**
     * Test where() with operator
     */
    public function testWhereOperator()
    {
        $users = $this->model->table('users')->where('age', '>', 30)->get();
        $this->assert(count($users) === 2, 'WHERE with operator - finds 2 users over 30');
    }

    /**
     * Test where() with array
     */
    public function testWhereArray()
    {
        $users = $this->model->table('users')->where(['status' => 'active', 'age' => 30])->get();
        $this->assert(count($users) === 1, 'WHERE array - finds Bob');
        $this->assert($users[0]['name'] === 'Bob', 'WHERE array - correct user');
    }

    /**
     * Test orWhere()
     */
    public function testOrWhere()
    {
        $users = $this->model->table('users')->where('name', 'Alice')->orWhere('name', 'Bob')->get();
        $this->assert(count($users) === 2, 'OR WHERE - finds Alice and Bob');
    }

    /**
     * Test whereIn()
     */
    public function testWhereIn()
    {
        $users = $this->model->table('users')->whereIn('name', ['Alice', 'Bob', 'Charlie'])->get();
        $this->assert(count($users) === 3, 'WHERE IN - finds 3 users');
    }

    /**
     * Test whereNotIn()
     */
    public function testWhereNotIn()
    {
        $users = $this->model->table('users')->whereNotIn('name', ['Alice', 'Bob'])->get();
        $this->assert(count($users) === 3, 'WHERE NOT IN - finds 3 users');
    }

    /**
     * Test whereNull() and whereNotNull()
     */
    public function testWhereNull()
    {
        // First insert a user with null age
        $this->model->table('users')->insert(['name' => 'Test', 'email' => 'test@example.com', 'age' => null]);

        $users = $this->model->table('users')->whereNull('age')->get();
        $this->assert(count($users) >= 1, 'WHERE NULL - finds users with null age');

        $users = $this->model->table('users')->whereNotNull('age')->get();
        $this->assert(count($users) >= 5, 'WHERE NOT NULL - finds users with non-null age');
    }

    /**
     * Test whereBetween()
     */
    public function testWhereBetween()
    {
        $users = $this->model->table('users')->whereBetween('age', 26, 31)->get();
        $this->assert(count($users) === 2, 'WHERE BETWEEN - finds users aged 26-31');
    }

    /**
     * Test whereNotBetween()
     */
    public function testWhereNotBetween()
    {
        $users = $this->model->table('users')->whereNotBetween('age', 26, 31)->get();
        $this->assert(count($users) === 3, 'WHERE NOT BETWEEN - finds users not aged 26-31');
    }

    /**
     * Test orderBy()
     */
    public function testOrderBy()
    {
        $users = $this->model->table('users')->orderBy('age', 'DESC')->get();
        $this->assert($users[0]['name'] === 'Charlie', 'ORDER BY DESC - oldest user first');

        $users = $this->model->table('users')->orderBy('age', 'ASC')->get();
        $this->assert($users[0]['name'] === 'Alice', 'ORDER BY ASC - youngest user first');
    }

    /**
     * Test limit() and offset()
     */
    public function testLimitOffset()
    {
        $users = $this->model->table('users')->limit(2)->get();
        $this->assert(count($users) === 2, 'LIMIT - returns 2 users');

        $users = $this->model->table('users')->limit(2)->offset(2)->get();
        $this->assert(count($users) === 2, 'OFFSET - skips first 2 users');
    }

    /**
     * Test paginate()
     */
    public function testPaginate()
    {
        $users = $this->model->table('users')->paginate(2, 2)->get();
        $this->assert(count($users) === 2, 'PAGINATE - page 2 returns 2 users');
    }

    /**
     * Test join()
     */
    public function testJoin()
    {
        $results = $this->model->table('users')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->select('users.name', 'posts.title')
            ->get();

        $this->assert(count($results) === 5, 'JOIN - returns joined results');
        $this->assert(isset($results[0]['title']), 'JOIN - has post title');
    }

    /**
     * Test leftJoin()
     */
    public function testLeftJoin()
    {
        $results = $this->model->table('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->select('users.name', 'posts.title')
            ->get();

        $this->assert(count($results) >= 5, 'LEFT JOIN - includes all users');
    }

    /**
     * Test groupBy()
     */
    public function testGroupBy()
    {
        $results = $this->model->table('posts')
            ->select('user_id')
            ->selectRaw('COUNT(*) as post_count')
            ->groupBy('user_id')
            ->get();

        $this->assert(count($results) >= 1, 'GROUP BY - groups posts by user');
        $this->assert(isset($results[0]['post_count']), 'GROUP BY - has count');
    }

    /**
     * Test having()
     */
    public function testHaving()
    {
        $results = $this->model->table('posts')
            ->select('user_id')
            ->selectRaw('COUNT(*) as post_count')
            ->groupBy('user_id')
            ->having('COUNT(*)', '>', 1)
            ->get();

        $this->assert(count($results) >= 1, 'HAVING - filters grouped results');
    }

    /**
     * Test distinct()
     */
    public function testDistinct()
    {
        $results = $this->model->table('users')->distinct()->select('status')->get();
        $this->assert(count($results) === 2, 'DISTINCT - returns unique statuses');
    }

    /**
     * Test first()
     */
    public function testFirst()
    {
        $user = $this->model->table('users')->where('name', 'Alice')->first();
        $this->assert($user !== false, 'FIRST - returns a result');
        $this->assert($user['email'] === 'alice@example.com', 'FIRST - returns correct user');
    }

    /**
     * Test find()
     */
    public function testFind()
    {
        $user = $this->model->table('users')->find(1);
        $this->assert($user !== false, 'FIND - finds user by ID');
        $this->assert($user['id'] == 1, 'FIND - returns correct user');
    }

    /**
     * Test value()
     */
    public function testValue()
    {
        $email = $this->model->table('users')->where('id', 1)->value('email');
        $this->assert($email === 'alice@example.com', 'VALUE - returns single column value');
    }

    /**
     * Test pluck()
     */
    public function testPluck()
    {
        $names = $this->model->table('users')->pluck('name');
        $this->assert(count($names) >= 5, 'PLUCK - returns array of values');
        $this->assert(in_array('Alice', $names), 'PLUCK - contains expected value');
    }

    /**
     * Test count()
     */
    public function testCount()
    {
        $count = $this->model->table('users')->count();
        $this->assert($count >= 5, 'COUNT - returns correct count');

        $count = $this->model->table('users')->where('status', 'active')->count();
        $this->assert($count === 4, 'COUNT with WHERE - returns filtered count');
    }

    /**
     * Test max()
     */
    public function testMax()
    {
        $maxAge = $this->model->table('users')->max('age');
        $this->assert($maxAge == 35, 'MAX - returns maximum age');
    }

    /**
     * Test min()
     */
    public function testMin()
    {
        $minAge = $this->model->table('users')->min('age');
        $this->assert($minAge == 25, 'MIN - returns minimum age');
    }

    /**
     * Test avg()
     */
    public function testAvg()
    {
        $avgAge = $this->model->table('users')->avg('age');
        $this->assert($avgAge > 0, 'AVG - returns average age');
    }

    /**
     * Test sum()
     */
    public function testSum()
    {
        $totalViews = $this->model->table('posts')->sum('views');
        $this->assert($totalViews == 425, 'SUM - returns total views');
    }

    /**
     * Test exists()
     */
    public function testExists()
    {
        $exists = $this->model->table('users')->where('email', 'alice@example.com')->exists();
        $this->assert($exists === true, 'EXISTS - finds existing record');

        $exists = $this->model->table('users')->where('email', 'nonexistent@example.com')->exists();
        $this->assert($exists === false, 'EXISTS - returns false for non-existent record');
    }

    /**
     * Test insert()
     */
    public function testInsert()
    {
        $id = $this->model->table('users')->insert([
            'name' => 'Frank',
            'email' => 'frank@example.com',
            'age' => 40,
            'status' => 'active'
        ]);

        $this->assert($id > 0, 'INSERT - returns insert ID');

        $user = $this->model->table('users')->find($id);
        $this->assert($user['name'] === 'Frank', 'INSERT - record inserted correctly');
    }

    /**
     * Test update()
     */
    public function testUpdate()
    {
        $affected = $this->model->table('users')->where('name', 'Alice')->update([
            'age' => 26
        ]);

        $this->assert($affected === 1, 'UPDATE - returns affected rows');

        $user = $this->model->table('users')->where('name', 'Alice')->first();
        $this->assert($user['age'] == 26, 'UPDATE - record updated correctly');
    }

    /**
     * Test delete()
     */
    public function testDelete()
    {
        // Insert a user to delete
        $id = $this->model->table('users')->insert([
            'name' => 'ToDelete',
            'email' => 'delete@example.com',
            'age' => 99
        ]);

        $affected = $this->model->table('users')->where('id', $id)->delete();
        $this->assert($affected === 1, 'DELETE - returns affected rows');

        $user = $this->model->table('users')->find($id);
        $this->assert($user === false, 'DELETE - record deleted');
    }

    /**
     * Test increment()
     */
    public function testIncrement()
    {
        $this->model->table('posts')->where('id', 1)->increment('views', 10);
        $post = $this->model->table('posts')->find(1);
        $this->assert($post['views'] == 110, 'INCREMENT - increments value correctly');
    }

    /**
     * Test decrement()
     */
    public function testDecrement()
    {
        $this->model->table('posts')->where('id', 1)->decrement('views', 5);
        $post = $this->model->table('posts')->find(1);
        $this->assert($post['views'] == 105, 'DECREMENT - decrements value correctly');
    }

    /**
     * Test transactions
     */
    public function testTransactions()
    {
        try {
            $this->model->beginTransaction();

            $this->model->table('users')->insert([
                'name' => 'Transaction Test',
                'email' => 'transaction@example.com',
                'age' => 50
            ]);

            $this->model->commit();

            $user = $this->model->table('users')->where('email', 'transaction@example.com')->first();
            $this->assert($user !== false, 'TRANSACTION - commit works');
        } catch (Exception $e) {
            $this->model->rollback();
            $this->assert(false, 'TRANSACTION - commit failed');
        }

        // Test rollback
        try {
            $this->model->beginTransaction();

            $this->model->table('users')->insert([
                'name' => 'Rollback Test',
                'email' => 'rollback@example.com',
                'age' => 60
            ]);

            $this->model->rollback();

            $user = $this->model->table('users')->where('email', 'rollback@example.com')->first();
            $this->assert($user === false, 'TRANSACTION - rollback works');
        } catch (Exception $e) {
            $this->assert(false, 'TRANSACTION - rollback failed');
        }
    }

    /**
     * Test toSql() for query debugging
     */
    public function testToSql()
    {
        $sql = $this->model->table('users')->where('status', 'active')->orderBy('age', 'DESC')->toSql();

        $this->assert(strpos($sql, 'SELECT') !== false, 'TO SQL - contains SELECT');
        $this->assert(strpos($sql, 'FROM users') !== false, 'TO SQL - contains FROM users');
        $this->assert(strpos($sql, 'WHERE') !== false, 'TO SQL - contains WHERE');
        $this->assert(strpos($sql, 'ORDER BY') !== false, 'TO SQL - contains ORDER BY');
    }

    /**
     * Test raw queries
     */
    public function testRaw()
    {
        $stmt = $this->model->raw("SELECT * FROM users WHERE age > :age", ['age' => 30]);
        $users = $this->model->fetchAll($stmt);

        $this->assert(count($users) >= 2, 'RAW - executes raw query');
    }

    /**
     * Run all tests
     */
    public function runAll()
    {
        echo "\n========================================\n";
        echo "PHPWeave Query Builder Test Suite\n";
        echo "========================================\n\n";

        // Run all test methods
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0 && $method !== 'testTransactions') {
                $this->$method();
            }
        }

        // Run transaction tests last (they can affect other tests)
        $this->testTransactions();

        // Print summary
        echo "\n========================================\n";
        echo "Test Summary\n";
        echo "========================================\n";
        echo "Total Tests: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: {$this->passed} ✓\n";
        echo "Failed: {$this->failed} ✗\n";
        echo "Success Rate: " . round(($this->passed / ($this->passed + $this->failed)) * 100, 2) . "%\n";
        echo "========================================\n\n";

        if ($this->failed > 0) {
            echo "Failed tests:\n";
            foreach ($this->tests as $test) {
                if ($test['status'] === 'FAIL') {
                    echo "  - {$test['name']}" . (isset($test['message']) ? " ({$test['message']})" : "") . "\n";
                }
            }
            echo "\n";
        }

        return $this->failed === 0;
    }
}

// Run tests
$tester = new QueryBuilderTest();
$success = $tester->runAll();

exit($success ? 0 : 1);
