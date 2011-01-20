<?php
/**
 * Rabbit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://github.com/canddi/Zend_RabbitMQ/blob/master/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to hello@canddi.com so we can send you a copy immediately.
 *
 */

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