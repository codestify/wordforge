<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use WordForge\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    /**
     * @var \wpdb mock
     */
    protected $wpdb;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock of WordPress global $wpdb object
        $this->wpdb         = $this->getMockBuilder(\stdClass::class)
                                   ->addMethods(['prepare', 'query', 'get_results', 'insert', 'update', 'delete'])
                                   ->getMock();
        $this->wpdb->prefix = 'wp_';

        // Make the mock available globally
        global $wpdb;
        $wpdb = $this->wpdb;
    }

    /**
     * Get a fresh query builder instance.
     *
     * @param  string  $table
     *
     * @return QueryBuilder
     */
    protected function getBuilder($table = 'posts')
    {
        return new QueryBuilder($table);
    }

    /**
     * Test a basic select statement.
     */
    public function testBasicSelect()
    {
        // Test default select
        $builder = $this->getBuilder();
        $sql     = $builder->toSql();
        $this->assertEquals('SELECT * FROM wp_posts', $sql);

        // Test select with specific columns
        $builder = $this->getBuilder();
        $sql     = $builder->select(['id', 'post_title'])->toSql();
        $this->assertEquals('SELECT id, post_title FROM wp_posts', $sql);

        // Test select with multiple specific columns
        $builder = $this->getBuilder();
        $sql     = $builder->select(['id', 'post_title', 'post_content', 'post_date'])->toSql();
        $this->assertEquals('SELECT id, post_title, post_content, post_date FROM wp_posts', $sql);
    }

    /**
     * Test where clauses in SQL.
     */
    public function testWhereClause()
    {
        // Test basic where
        $builder = $this->getBuilder();
        $sql     = $builder->where('id', 1)->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE id = %s', $sql);

        // Test where with explicit operator
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_status', '!=', 'trash')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_status != %s', $sql);

        // Test multiple where conditions (AND)
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_type', 'post')->where('post_status', 'publish')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_type = %s AND post_status = %s', $sql);

        // Test OR where
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_type', 'post')->orWhere('post_type', 'page')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_type = %s OR post_type = %s', $sql);

        // Test where with array of conditions
        $builder = $this->getBuilder();
        $sql     = $builder->where([
            'post_type'   => 'post',
            'post_status' => 'publish'
        ])->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_type = %s AND post_status = %s', $sql);
    }

    /**
     * Test whereIn clause in SQL.
     */
    public function testWhereInClause()
    {
        // Test whereIn
        $builder = $this->getBuilder();
        $sql     = $builder->whereIn('id', [1, 2, 3])->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE id IN (%s, %s, %s)', $sql);

        // Test whereNotIn
        $builder = $this->getBuilder();
        $sql     = $builder->whereNotIn('id', [1, 2, 3])->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE id NOT IN (%s, %s, %s)', $sql);

        // Test orWhereIn
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_type', 'post')->orWhereIn('id', [1, 2, 3])->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_type = %s OR id IN (%s, %s, %s)', $sql);
    }

    /**
     * Test whereNull clause in SQL.
     */
    public function testWhereNullClause()
    {
        // Test whereNull
        $builder = $this->getBuilder();
        $sql     = $builder->whereNull('post_parent')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_parent IS NULL', $sql);

        // Test whereNotNull
        $builder = $this->getBuilder();
        $sql     = $builder->whereNotNull('post_parent')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_parent IS NOT NULL', $sql);

        // Test combined with other where clauses
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_type', 'post')->whereNull('post_parent')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_type = %s AND post_parent IS NULL', $sql);
    }

    /**
     * Test whereBetween clause in SQL.
     */
    public function testWhereBetweenClause()
    {
        // Test whereBetween
        $builder = $this->getBuilder();
        $sql     = $builder->whereBetween('post_date', ['2023-01-01', '2023-12-31'])->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_date BETWEEN %s AND %s', $sql);

        // Test whereNotBetween
        $builder = $this->getBuilder();
        $sql     = $builder->whereNotBetween('post_date', ['2023-01-01', '2023-12-31'])->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_date NOT BETWEEN %s AND %s', $sql);

        // Test combined with other where clauses
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_type', 'post')->whereBetween('post_date', ['2023-01-01', '2023-12-31'])->toSql(
        );
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_type = %s AND post_date BETWEEN %s AND %s', $sql);
    }

    /**
     * Test whereLike clause in SQL.
     */
    public function testWhereLikeClause()
    {
        // Test whereLike
        $builder = $this->getBuilder();
        $sql     = $builder->whereLike('post_title', '%WordPress%')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_title LIKE %s', $sql);

        // Test combined with other where clauses
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_type', 'post')->whereLike('post_title', '%WordPress%')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_type = %s AND post_title LIKE %s', $sql);
    }

    /**
     * Test whereNested clause in SQL.
     */
    public function testWhereNestedClause()
    {
        // Test whereNested
        $builder = $this->getBuilder();
        $sql     = $builder->whereNested(function ($query) {
            $query->where('post_type', 'post')->orWhere('post_type', 'page');
        })->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE (post_type = %s OR post_type = %s)', $sql);

        // Test complex nested where
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_status', 'publish')
                           ->whereNested(function ($query) {
                               $query->where('post_type', 'post')
                                     ->orWhere('post_type', 'page');
                           })
                           ->whereNotNull('post_title')
                           ->toSql();

        $this->assertEquals(
            'SELECT * FROM wp_posts WHERE post_status = %s AND (post_type = %s OR post_type = %s) AND post_title IS NOT NULL',
            $sql
        );
    }

    /**
     * Test whereRaw clause in SQL.
     */
    public function testWhereRawClause()
    {
        // Test whereRaw
        $builder = $this->getBuilder();
        $sql     = $builder->whereRaw('post_date > DATE_SUB(NOW(), INTERVAL 30 DAY)')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts WHERE post_date > DATE_SUB(NOW(), INTERVAL 30 DAY)', $sql);

        // Test combined with other where clauses
        $builder = $this->getBuilder();
        $sql     = $builder->where('post_type', 'post')
                           ->whereRaw('post_date > DATE_SUB(NOW(), INTERVAL 30 DAY)')
                           ->toSql();

        $this->assertEquals(
            'SELECT * FROM wp_posts WHERE post_type = %s AND post_date > DATE_SUB(NOW(), INTERVAL 30 DAY)',
            $sql
        );
    }

    /**
     * Test join clauses in SQL.
     */
    public function testJoinClause()
    {
        // Test inner join
        $builder = $this->getBuilder();
        $sql     = $builder->join('postmeta', 'posts.ID', '=', 'postmeta.post_id')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts INNER JOIN wp_postmeta ON posts.ID = postmeta.post_id', $sql);

        // Test left join
        $builder = $this->getBuilder();
        $sql     = $builder->leftJoin('postmeta', 'posts.ID', '=', 'postmeta.post_id')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts LEFT JOIN wp_postmeta ON posts.ID = postmeta.post_id', $sql);

        // Test right join
        $builder = $this->getBuilder();
        $sql     = $builder->rightJoin('postmeta', 'posts.ID', '=', 'postmeta.post_id')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts RIGHT JOIN wp_postmeta ON posts.ID = postmeta.post_id', $sql);

        // Test raw join expression
        $builder = $this->getBuilder();
        $sql     = $builder->join('postmeta', 'posts.ID = postmeta.post_id')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts INNER JOIN wp_postmeta ON posts.ID = postmeta.post_id', $sql);

        // Test multiple joins
        $builder = $this->getBuilder();
        $sql     = $builder->join('postmeta', 'posts.ID', '=', 'postmeta.post_id')
                           ->join('users', 'posts.post_author', '=', 'users.ID')
                           ->toSql();

        $this->assertEquals(
            'SELECT * FROM wp_posts INNER JOIN wp_postmeta ON posts.ID = postmeta.post_id INNER JOIN wp_users ON posts.post_author = users.ID',
            $sql
        );
    }

    /**
     * Test order by clause in SQL.
     */
    public function testOrderByClause()
    {
        // Test orderBy
        $builder = $this->getBuilder();
        $sql     = $builder->orderBy('post_date')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts ORDER BY post_date ASC', $sql);

        // Test orderBy with direction
        $builder = $this->getBuilder();
        $sql     = $builder->orderBy('post_date', 'desc')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts ORDER BY post_date DESC', $sql);

        // Test orderByDesc
        $builder = $this->getBuilder();
        $sql     = $builder->orderByDesc('post_date')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts ORDER BY post_date DESC', $sql);

        // Test multiple orderBy
        $builder = $this->getBuilder();
        $sql     = $builder->orderBy('post_date', 'desc')->orderBy('post_title')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts ORDER BY post_date DESC, post_title ASC', $sql);

        // Test orderByRaw
        $builder = $this->getBuilder();
        $sql     = $builder->orderByRaw('FIELD(post_status, "publish", "draft", "pending")')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts ORDER BY FIELD(post_status, "publish", "draft", "pending")', $sql);
    }

    /**
     * Test group by clause in SQL.
     */
    public function testGroupByClause()
    {
        // Test groupBy with single column
        $builder = $this->getBuilder();
        $sql     = $builder->groupBy('post_type')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts GROUP BY post_type', $sql);

        // Test groupBy with multiple columns
        $builder = $this->getBuilder();
        $sql     = $builder->groupBy(['post_type', 'post_status'])->toSql();
        $this->assertEquals('SELECT * FROM wp_posts GROUP BY post_type, post_status', $sql);

        // Test with orderBy
        $builder = $this->getBuilder();
        $sql     = $builder->groupBy('post_type')->orderBy('post_date', 'desc')->toSql();
        $this->assertEquals('SELECT * FROM wp_posts GROUP BY post_type ORDER BY post_date DESC', $sql);
    }

    /**
     * Test having clause in SQL.
     */
    public function testHavingClause()
    {
        // Test having
        $builder = $this->getBuilder();
        $sql     = $builder->select(['post_type', 'COUNT(*) as count'])
                           ->groupBy('post_type')
                           ->having('count', '>', 5)
                           ->toSql();

        $this->assertEquals(
            'SELECT post_type, COUNT(*) as count FROM wp_posts GROUP BY post_type HAVING count > %s',
            $sql
        );

        // Test multiple having clauses
        $builder = $this->getBuilder();
        $sql     = $builder->select(['post_type', 'post_status', 'COUNT(*) as count'])
                           ->groupBy(['post_type', 'post_status'])
                           ->having('count', '>', 5)
                           ->having('post_type', '=', 'post')
                           ->toSql();

        $this->assertEquals(
            'SELECT post_type, post_status, COUNT(*) as count FROM wp_posts GROUP BY post_type, post_status HAVING count > %s AND post_type = %s',
            $sql
        );
    }

    /**
     * Test limit and offset clauses in SQL.
     */
    public function testLimitOffsetClause()
    {
        // Test limit
        $builder = $this->getBuilder();
        $sql     = $builder->limit(10)->toSql();
        $this->assertEquals('SELECT * FROM wp_posts LIMIT 10', $sql);

        // Test offset
        $builder = $this->getBuilder();
        $sql     = $builder->offset(5)->toSql();
        $this->assertEquals('SELECT * FROM wp_posts OFFSET 5', $sql);

        // Test limit and offset together
        $builder = $this->getBuilder();
        $sql     = $builder->limit(10)->offset(5)->toSql();
        $this->assertEquals('SELECT * FROM wp_posts LIMIT 10 OFFSET 5', $sql);

        // Test take (alias for limit)
        $builder = $this->getBuilder();
        $sql     = $builder->take(10)->toSql();
        $this->assertEquals('SELECT * FROM wp_posts LIMIT 10', $sql);

        // Test skip (alias for offset)
        $builder = $this->getBuilder();
        $sql     = $builder->skip(5)->toSql();
        $this->assertEquals('SELECT * FROM wp_posts OFFSET 5', $sql);
    }

    /**
     * Test complex queries with multiple clauses.
     */
    public function testComplexQueries()
    {
        // Test a complex select query with multiple clauses
        $builder = $this->getBuilder();
        $sql     = $builder->select(['p.ID', 'p.post_title', 'p.post_date', 'u.display_name'])
                           ->where('p.post_type', 'post')
                           ->where('p.post_status', 'publish')
                           ->join('users as u', 'p.post_author', '=', 'u.ID')
                           ->leftJoin('postmeta as pm', 'p.ID', '=', 'pm.post_id')
                           ->whereNotNull('pm.meta_value')
                           ->whereBetween('p.post_date', ['2023-01-01', '2023-12-31'])
                           ->groupBy('p.ID')
                           ->orderBy('p.post_date', 'desc')
                           ->limit(10)
                           ->offset(0)
                           ->toSql();

        $expected = 'SELECT p.ID, p.post_title, p.post_date, u.display_name FROM wp_posts '.
                    'INNER JOIN wp_users as u ON p.post_author = u.ID '.
                    'LEFT JOIN wp_postmeta as pm ON p.ID = pm.post_id '.
                    'WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_value IS NOT NULL '.
                    'AND p.post_date BETWEEN %s AND %s '.
                    'GROUP BY p.ID '.
                    'ORDER BY p.post_date DESC '.
                    'LIMIT 10 OFFSET 0';

        $this->assertEquals($expected, $sql);
    }

    /**
     * Test aggregate functions in SQL.
     */
    public function testAggregateFunctions()
    {
        // Test COUNT - We don't call the actual function but verify the SQL
        $builder = $this->getBuilder();
        $builder->select(['COUNT(*) as aggregate']);
        $this->assertEquals('SELECT COUNT(*) as aggregate FROM wp_posts', $builder->toSql());

        // Test MAX
        $builder = $this->getBuilder();
        $builder->select(['MAX(post_id) as aggregate']);
        $this->assertEquals('SELECT MAX(post_id) as aggregate FROM wp_posts', $builder->toSql());

        // Test AVG
        $builder = $this->getBuilder();
        $builder->select(['AVG(post_views) as aggregate']);
        $this->assertEquals('SELECT AVG(post_views) as aggregate FROM wp_posts', $builder->toSql());
    }

    /**
     * Test the SQL for insert statements.
     */
    public function testInsertSql()
    {
        // For insert, we need to mock wpdb->insert
        $this->wpdb->expects($this->once())
                   ->method('insert')
                   ->with(
                       'wp_posts',
                       ['post_title' => 'Test Post', 'post_content' => 'Test Content']
                   )
                   ->willReturn(true);

        $this->wpdb->insert_id = 123;

        $builder = $this->getBuilder();
        $result  = $builder->insert([
            'post_title'   => 'Test Post',
            'post_content' => 'Test Content'
        ]);

        $this->assertEquals(123, $result);
    }

    /**
     * Test the SQL for update statements.
     */
    public function testUpdateSql()
    {
        // Test update with where clause
        $data = ['post_title' => 'Updated Post'];

        $builder = $this->getBuilder();
        // Update without where should fail
        $result = $builder->update($data);
        $this->assertFalse($result);

        $builder = $this->getBuilder();
        $builder->where('id', 1);

        // We need to capture the SQL that would be prepared
        $this->wpdb->expects($this->once())
                   ->method('prepare')
                   ->with(
                       $this->stringContains('UPDATE wp_posts SET post_title = %s WHERE id = %s'),
                       $this->anything()
                   )
                   ->willReturn('UPDATE wp_posts SET post_title = "Updated Post" WHERE id = 1');

        $this->wpdb->expects($this->once())
                   ->method('query')
                   ->with('UPDATE wp_posts SET post_title = "Updated Post" WHERE id = 1')
                   ->willReturn(1);

        $result = $builder->update($data);
        $this->assertEquals(1, $result);
    }

    /**
     * Test the SQL for delete statements.
     */
    public function testDeleteSql()
    {
        $builder = $this->getBuilder();
        // Delete without where should fail
        $result = $builder->delete();
        $this->assertFalse($result);

        $builder = $this->getBuilder();
        $builder->where('id', 1);

        // We need to capture the SQL that would be prepared
        $this->wpdb->expects($this->once())
                   ->method('prepare')
                   ->with(
                       $this->stringContains('DELETE FROM wp_posts WHERE id = %s'),
                       $this->anything()
                   )
                   ->willReturn('DELETE FROM wp_posts WHERE id = 1');

        $this->wpdb->expects($this->once())
                   ->method('query')
                   ->with('DELETE FROM wp_posts WHERE id = 1')
                   ->willReturn(1);

        $result = $builder->delete();
        $this->assertEquals(1, $result);
    }

    /**
     * Test raw query SQL.
     */
    /**
     * Test raw query SQL.
     */
    public function testRawSql()
    {
        // Create a new instance of the mock for each test
        $this->setUp();

        // Test raw query with no bindings
        $this->wpdb->expects($this->once())
                   ->method('get_results')
                   ->with('SELECT * FROM wp_posts WHERE post_type = "post"')
                   ->willReturn([]);

        $builder = $this->getBuilder();
        $builder->raw('SELECT * FROM wp_posts WHERE post_type = "post"');

        // Create a fresh mock for the second test
        $this->setUp();

        // Test raw query with bindings
        $this->wpdb->expects($this->once())
                   ->method('prepare')
                   ->with(
                       'SELECT * FROM wp_posts WHERE post_type = %s',
                       ['post']
                   )
                   ->willReturn('SELECT * FROM wp_posts WHERE post_type = "post"');

        $this->wpdb->expects($this->once())
                   ->method('get_results')
                   ->with('SELECT * FROM wp_posts WHERE post_type = "post"')
                   ->willReturn([]);

        $builder = $this->getBuilder();
        $builder->raw('SELECT * FROM wp_posts WHERE post_type = %s', ['post']);
    }

    /**
     * Test transaction SQL.
     */
    /**
     * Test transaction SQL.
     */
    public function testTransactionSql()
    {
        // Test START TRANSACTION
        $this->setUp();  // Reset the mock
        $this->wpdb->expects($this->once())
                   ->method('query')
                   ->with('START TRANSACTION')
                   ->willReturn(true);

        $builder = $this->getBuilder();
        $result  = $builder->beginTransaction();
        $this->assertTrue($result);

        // Test COMMIT
        $this->setUp();  // Reset the mock again
        $this->wpdb->expects($this->once())
                   ->method('query')
                   ->with('COMMIT')
                   ->willReturn(true);

        $builder = $this->getBuilder();
        $result  = $builder->commit();
        $this->assertTrue($result);

        // Test ROLLBACK
        $this->setUp();  // Reset the mock once more
        $this->wpdb->expects($this->once())
                   ->method('query')
                   ->with('ROLLBACK')
                   ->willReturn(true);

        $builder = $this->getBuilder();
        $result  = $builder->rollback();
        $this->assertTrue($result);
    }
}