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
namespace Cake\ORM;

/**
 * An Association is a relationship established between two tables and is used
 * as a configuration place for customizing the way interconnected records are
 * retrieved.
 *
 */
abstract class Association {

/**
 * Name given to the association, it usually represents the alias
 * assigned to the target associated table
 *
 * @var string
 */
	protected $_name;

/**
 * Whether this association can be expressed directly in a query join
 *
 * @var boolean
 */
	protected $_canBeJoined = false;

/**
 * The className of the target table object
 *
 * @var string
 */
	protected $_className;

/**
 * The name of the field representing the foreign key to the target table
 *
 * @var string
 */
	protected $_foreignKey;

/**
 * A list of conditions to be always included when fetching records from
 * the target association
 *
 * @var array
 */
	protected $_conditions = [];

/**
 * Whether the records on the target table are dependent on the source table,
 * often used to indicate that records should be removed is the owning record in
 * the source table is deleted.
 *
 * @var boolean
 */
	protected $_dependent = false;

/**
 * Source table instance
 *
 * @var Cake\ORM\Table
 */
	protected $_sourceTable;

/**
 * Target table instance
 *
 * @var Cake\ORM\Table
 */
	protected $_targetTable;

/**
 * The type of join to be used when adding the association to a query
 *
 * @var string
 */
	protected $_joinType = 'LEFT';

/**
 * The property name that should be filled with data from the target table
 * in the source table record.
 *
 * @var string
 */
	protected $_property;

/**
 * Constructor. Subclasses can override _options function to get the original
 * list of passed options if expecting any other special key
 *
 * @param string $name The name given to the association
 * @param array $options A list of properties to be set on this object
 * @return void
 */
	public function __construct($name, array $options = []) {
		$defaults = [
			'className',
			'foreignKey',
			'conditions',
			'dependent',
			'sourceTable',
			'targetTable',
			'joinType',
			'property'
		];
		foreach ($defaults as $property) {
			if (isset($options[$property])) {
				$this->{'_' . $property} = $options[$property];
			}
		}

		$this->_name = $name;
		$this->_options($options);

		if (empty($this->_className)) {
			$this->_className = $this->_name;
		}

		if (empty($this->_property)) {
			$this->property($name);
		}
	}

/**
 * Sets the name for this association. If no argument is passed then the current
 * configured name will be returned
 *
 * @param string $name Name to be assigned
 * @return string
 */
	public function name($name = null) {
		if ($name !== null) {
			$this->_name = $name;
		}
		return $this->_name;
	}

/**
 * Sets the table instance for the source side of the association. If no arguments
 * are passed, the current configured table instance is returned
 *
 * @param Cake\ORM\Table $table the instance to be assigned as source side
 * @return Cake\ORM\Table
 */
	public function source(Table $table = null) {
		if ($table === null) {
			return $this->_sourceTable;
		}
		return $this->_sourceTable = $table;
	}

/**
 * Sets the table instance for the target side of the association. If no arguments
 * are passed, the current configured table instance is returned
 *
 * @param Cake\ORM\Table $table the instance to be assigned as target side
 * @return Cake\ORM\Table
 */
	public function target(Table $table = null) {
		if ($table === null && $this->_targetTable) {
			return $this->_targetTable;
		}

		if ($table !== null) {
			return $this->_targetTable = $table;
		}

		if ($table === null && $this->_className !== null) {
			$this->_targetTable = Table::build(
				$this->_name,
				['className' => $this->_className]
			);
		}
		return $this->_targetTable;
	}

/**
 * Sets a list of conditions to be always included when fetching records from
 * the target association. If no parameters are passed current list is returned
 *
 * @param array $conditions list of conditions to be used
 * @see Cake\Database\Query::where() for examples on the format of the array
 * @return array
 */
	public function conditions($conditions = null) {
		if ($conditions !== null) {
			$this->_conditions = $conditions;
		}
		return $this->_conditions;
	}

/**
 * Sets the name of the field representing the foreign key to the target table.
 * If no parameters are passed current field is returned
 *
 * @param string $key the key to be used to link both tables together
 * @return string
 */
	public function foreignKey($key = null) {
		if ($key !== null) {
			$this->_foreignKey = $key;
		}
		return $this->_foreignKey;
	}

/**
 * Sets Whether the records on the target table are dependent on the source table,
 * often used to indicate that records should be removed is the owning record in
 * the source table is deleted.
 * If no parameters are passed current setting is returned.
 *
 * @param boolean $dependent
 * @return boolean
 */
	public function dependent($dependent = null) {
		if ($dependent !== null) {
			$this->_dependent = $dependent;
		}
		return $this->_dependent;
	}

/**
 * Whether this association can be expressed directly in a query join
 *
 * @return boolean
 */
	public function canBeJoined() {
		return $this->_canBeJoined;
	}

/**
 * Sets the type of join to be used when adding the association to a query.
 * If no arguments are passed, currently configured type is returned.
 *
 * @param string $type the join type to be used (e.g. INNER)
 * @return string
 */
	public function joinType($type = null) {
		if ($type === null) {
			return $this->_joinType;
		}
		return $this->_joinType = $type;
	}

/**
 * Sets the property name that should be filled with data from the target table
 * in the source table record.
 * If no arguments are passed, currently configured type is returned.
 *
 * @return string
 */
	function property($name = null) {
		if ($name !== null) {
			$this->_property = $name;
		}
		return $this->_property;
	}

/**
 * Override this function to initialize any concrete association class, it will
 * get passed the original list of options used in the constructor
 *
 * @param array $options List of options used for initialization
 * @return void
 */
	protected  function _options(array $options) {
	}

/**
 * Alters a Query object to include the associated target table data in the final
 * result
 *
 * @param Query $query the query to be altered to include the target table data
 * @param array $options Any extra options or overrides to be taken in account
 * @return void
 */
	public abstract function attachTo(Query $query, array $options = []);

}