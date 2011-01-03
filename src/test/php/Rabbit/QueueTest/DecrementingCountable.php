<?php
/**
 * @category Rabbit   
 * @package  Test
 * @subpackage Rabbit_QueueTest
 * @copyright
 * @license    
 * @author   Franco Zeoli
 */

/**
 * Provides an utility class for ocasions where a countable thingy is needed,
 * and decrements each time the count is consulted.
 */
class Rabbit_QueueTest_DecrementingCountable implements Countable
{
	private $_count;
	
	/**
	 * Creates a new decrementing countable with the amount of items given.
	 * 
	 * @param int $count The initial amount.
	 */
	public function __construct($count = 1)
	{
		$this->_count = $count;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Countable::count()
	 */
	public function count ()
	{
		if ($this->_count < 0) {
			throw new Exception('The count reached -1');
		}
		
		return $this->_count--;
	}
	
}