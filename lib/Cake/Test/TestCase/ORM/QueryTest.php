<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\ORM;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
 * Tests Query class
 *
 */
class QueryTest extends \Cake\TestSuite\TestCase {

	public function setUp() {
		$this->connection = new Connection(Configure::read('Datasource.test'));
		$schema = ['id' => ['type' => 'integer']];
		$schema1 = [
			'id' => ['type' => 'integer'],
			'name' => ['type' => 'string'],
			'phone' => ['type' => 'string']
		];
		$schema2 = [
			'id' => ['type' => 'integer'],
			'total' => ['type' => 'string'],
			'placed' => ['type' => 'datetime']
		];

		$this->table = $table = Table::build('foo', ['schema' => $schema]);
		$clients = Table::build('client', ['schema' => $schema1]);
		$orders = Table::build('order', ['schema' => $schema2]);
		$companies = Table::build('company', ['schema' => $schema, 'table' => 'organizations']);
		$orderTypes = Table::build('orderType', ['schema' => $schema]);
		$stuff = Table::build('stuff', ['schema' => $schema, 'table' => 'things']);
		$stuffTypes = Table::build('stuffType', ['schema' => $schema]);
		$categories = Table::build('category', ['schema' => $schema]);

		$table->belongsTo('client');
		$clients->hasOne('order');
		$clients->belongsTo('company');
		$orders->belongsTo('orderType');
		$orders->hasOne('stuff');
		$stuff->belongsTo('stuffType');
		$companies->belongsTo('category');
	}

	public function tearDown() {
		$this->connection->execute('DROP TABLE IF EXISTS articles');
		$this->connection->execute('DROP TABLE IF EXISTS authors');
		Table::clearRegistry();
	}


/**
 * Test helper for creating tables.
 *
 * @return void
 */
	protected function _createAuthorsAndArticles() {
		$table = 'CREATE TEMPORARY TABLE authors(id int, name varchar(50))';
		$this->connection->execute($table);

		$table = 'CREATE TEMPORARY TABLE articles(id int, title varchar(20), body varchar(50), author_id int)';
		$this->connection->execute($table);

		Table::config('authors', ['connection' => $this->connection]);
		Table::config('articles', ['connection' => $this->connection]);
	}

/**
 * Auxiliary function to insert a couple rows in a newly created table
 *
 * @return void
 */
	protected function _insertTwoRecords() {
		$this->_createAuthorsAndArticles();

		$data = ['id' => '1', 'name' => 'Chuck Norris'];
		$result = $this->connection->insert('authors', $data, ['id' => 'integer', 'name' => 'string']);

		$result->bindValue(1, '2', 'integer');
		$result->bindValue(2, 'Bruce Lee');
		$result->execute();

		$data = ['id' => '1', 'title' => 'a title', 'body' => 'a body', 'author_id' => 1];
		$result = $this->connection->insert(
			'articles',
			$data,
			['id' => 'integer', 'title' => 'string', 'body' => 'string', 'author_id' => 'integer']
		);

		$result->bindValue(1, '2', 'integer');
		$result->bindValue(2, 'another title');
		$result->bindValue(3, 'another body');
		$result->bindValue(4, 2);
		$result->execute();

		return $result;
	}

/**
 * Tests that fully defined belongsTo and hasOne relationships are joined correctly
 *
 * @return void
 **/
	public function testContainToJoinsOneLevel() {
		$contains = [
			'client' => [
			'order' => [
					'orderType',
					'stuff' => ['stuffType']
				],
				'company' => [
					'foreignKey' => 'organization_id',
					'category'
				]
			]
		];

		$query = $this->getMock('\Cake\ORM\Query', ['join'], [$this->connection]);

		$query->expects($this->at(0))->method('join')
			->with(['client' => [
				'table' => 'clients',
				'type' => 'LEFT',
				'conditions' => ['client.id = foo.client_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(1))->method('join')
			->with(['order' => [
				'table' => 'orders',
				'type' => 'INNER',
				'conditions' => ['client.id = order.client_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(2))->method('join')
			->with(['orderType' => [
				'table' => 'order_types',
				'type' => 'LEFT',
				'conditions' => ['orderType.id = order.order_type_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(3))->method('join')
			->with(['stuff' => [
				'table' => 'things',
				'type' => 'INNER',
				'conditions' => ['order.id = stuff.order_id']
			]])
			->will($this->returnValue($query));


		$query->expects($this->at(4))->method('join')
			->with(['stuffType' => [
				'table' => 'stuff_types',
				'type' => 'LEFT',
				'conditions' => ['stuffType.id = stuff.stuff_type_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(5))->method('join')
			->with(['company' => [
				'table' => 'organizations',
				'type' => 'LEFT',
				'conditions' => ['company.id = client.organization_id']
			]])
			->will($this->returnValue($query));

		$query->expects($this->at(6))->method('join')
			->with(['category' => [
				'table' => 'categories',
				'type' => 'LEFT',
				'conditions' => ['category.id = company.category_id']
			]])
			->will($this->returnValue($query));

		$s = $query
			->select('foo.id')
			->repository($this->table)
			->contain($contains)->sql();
	}

/**
 * Test that fields for contained models are aliased and added to the select clause
 *
 * @return void
 **/
	public function testContainToFieldsPredefined() {
		$contains = [
			'client' => [
				'fields' => ['name', 'company_id', 'client.telephone'],
				'order' => [
					'fields' => ['total', 'placed']
				]
			]
		];

		$table = Table::build('foo', ['schema' => ['id' => ['type' => 'integer']]]);
		$query = new Query($this->connection);

		$query->select('foo.id')->repository($table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id', 'client__name' => 'client.name',
			'client__company_id' => 'client.company_id',
			'client__telephone' => 'client.telephone',
			'order__total' => 'order.total', 'order__placed' => 'order.placed'
		];
		$this->assertEquals($expected, $select);
	}


/**
 * Tests that default fields for associations are added to the select clause when
 * none is specified
 *
 * @return void
 **/
	public function testContainToFieldsDefault() {
		$contains = ['client' => ['order']];

		$query = new Query($this->connection);
		$query->select()->repository($this->table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id', 'client__name' => 'client.name',
			'client__id' => 'client.id', 'client__phone' => 'client.phone',
			'order__id' => 'order.id', 'order__total' => 'order.total',
			'order__placed' => 'order.placed'
		];
		$this->assertEquals($expected, $select);

		$contains['client']['fields'] = ['name'];
		$query = new Query($this->connection);
		$query->select('foo.id')->repository($this->table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = ['foo__id' => 'foo.id', 'client__name' => 'client.name'];
		$this->assertEquals($expected, $select);

		$contains['client']['fields'] = [];
		$contains['client']['order']['fields'] = false;
		$query = new Query($this->connection);
		$query->select()->repository($this->table)->contain($contains)->sql();
		$select = $query->clause('select');
		$expected = [
			'foo__id' => 'foo.id',
			'client__id' => 'client.id',
			'client__name' => 'client.name',
			'client__phone' => 'client.phone',
		];
		$this->assertEquals($expected, $select);
	}

/**
 * Tests that results are grouped correctly when using contain()
 *
 * @return void
 **/
	public function testContainResultFetchingOneLevel() {
		$this->_insertTwoRecords();

		$query = new Query($this->connection);
		$table = Table::build('article', ['table' => 'articles']);
		Table::build('author', ['connection' => $this->connection]);
		$table->belongsTo('author');
		$results = $query->repository($table)->select()->contain('author')->toArray();
		$expected = [
			[
				'id' => 1,
				'title' => 'a title',
				'body' => 'a body',
				'author_id' => 1,
				'author' => [
					'id' => 1,
					'name' => 'Chuck Norris'
				]
			],
			[
				'id' => 2,
				'title' => 'another title',
				'body' => 'another body',
				'author_id' => 2,
				'author' => [
					'id' => 2,
					'name' => 'Bruce Lee'
				]
			]
		];
		$this->assertSame($expected, $results);
	}

/**
 * Tests that HasMany associations are correctly eager loaded
 *
 * @return void
 **/
	public function testHasManyEagerLoading() {
		$this->_insertTwoRecords();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles']);

		$results = $query->repository($table)->select()->contain('article')->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'Chuck Norris',
				'articles' => [
					[
						'id' => 1,
						'title' => 'a title',
						'body' => 'a body',
						'author_id' => 1
					]
				]
			],
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					[
						'id' => 2,
						'title' => 'another title',
						'body' => 'another body',
						'author_id' => 2
					]
				]
			]
		];
		$this->assertEquals($expected, $results);

		$results = $query->repository($table)
			->select()
			->contain(['article' => ['conditions' => ['id' => 2]]])
			->toArray();
		unset($expected[0]['articles']);
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that it is possible to select only certain fields on
 * eagerly loaded has many associations
 *
 * @return void
 **/
	public function testHasManyEagerLoadingFields() {
		$this->_insertTwoRecords();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles']);

		$results = $query->repository($table)
			->select()
			->contain(['article' => ['fields' => ['title', 'author_id']]])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'Chuck Norris',
				'articles' => [
					['title' => 'a title', 'author_id' => 1]
				]
			],
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					['title' => 'another title', 'author_id' => 2]
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

/**
 * Tests that not selecting the foreignKey for a hasMany association will
 * throw an exception
 *
 * @expectedException \InvalidArgumentException
 * @expectedExceptionMessage You are required to select the "article.author_id"
 * @return void
 **/
	public function testHasManyEagerLoadingFieldsError() {
		$this->_insertTwoRecords();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles']);

		$results = $query->repository($table)
			->select()
			->contain(['article' => ['fields' => ['title']]])
			->toArray();
	}

/**
 * Tests that it is possible to set an order in a hasMany result set
 *
 * @return void
 **/
	public function testHasManyEagerLoadingOrder() {
		$statement = $this->_insertTwoRecords();
		$statement->bindValue(1, 3, 'integer');
		$statement->bindValue(2, 'a fine title');
		$statement->bindValue(3, 'a fine body');
		$statement->bindValue(4, 2);
		$statement->execute();

		$query = new Query($this->connection);
		$table = Table::build('author', ['connection' => $this->connection]);
		Table::build('article', ['connection' => $this->connection]);
		$table->hasMany('article', ['property' => 'articles']);

		$results = $query->repository($table)
			->select()
			->contain([
				'article' => [
					'fields' => ['title', 'author_id'],
					'sort' => ['id' => 'DESC']
				]
			])
			->toArray();
		$expected = [
			[
				'id' => 1,
				'name' => 'Chuck Norris',
				'articles' => [
					['title' => 'a title', 'author_id' => 1]
				]
			],
			[
				'id' => 2,
				'name' => 'Bruce Lee',
				'articles' => [
					['title' => 'a fine title', 'author_id' => 2],
					['title' => 'another title', 'author_id' => 2],
				]
			]
		];
		$this->assertEquals($expected, $results);
	}

}