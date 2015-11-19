<?php

namespace Aw\Common\Spl\Iterators;

use \Iterator,
    \Exception,
    \Countable,
    \PDOStatement;

/**
 * PdoStatementIterator
 * Iterates over PDO statement without loading whole result in memory at once.
 * @Warning: This iterator cannot be rewinded.
 * @author Jerry Sietsma
 */
class PdoStatementIterator implements Iterator, Countable
{
	/**
	 * @var	object	$stmt
	 */
	protected $stmt;
	
	protected $position;
	
	protected $current;
	
	protected $next;
		
	public function __construct(PDOStatement $stmt, $fetchMode = null)
	{
		$this->stmt = $stmt;
		$this->position = -1;
		
		if ($fetchMode !== null)
		{
			$this->stmt->setFetchMode($fetchMode);
		}
	}
	
	public function getStmt()
	{
		return $this->stmt;
	}
	
	/**
	 * Iterator implementation
	 */
	
	public function key()
	{
		return $this->position;
	}
	
	public function current()
	{
		return $this->current;
	}
	
	public function next()
	{
		$this->current = $this->stmt->fetch();
		$this->position++;
				
		return $this->current !== false;
	}
	
	public function valid()
	{
		return $this->current !== false;
	}
	
	public function rewind()
	{
		if ($this->position > 0)
		{
			throw new Exception(get_called_class() . ' doesn\'t support rewind.');
		}
		
		$this->next();
	}
	
	/**
	 * Countable implementation
	 */	
	
	public function count()
	{
		return $this->stmt->rowCount();
	}
}