<?php
/**
 * @category Rabbit   
 * @package  Test
 * @copyright
 * @license    
 * @author   Franco Zeoli
 */
class Rabbit_QueueTest extends PHPUnit_Framework_TestCase
{
	const QUEUE_NAME = 'test';
	
	/**
	 * Tears down the test.
	 * 
	 * @return void
	 */
	public function teardown()
	{
		Mockery::close();
	}

	/**
	 * Tests the queue's constructor.
	 * 
	 * @return void
	 */
	public function testConstruct()
	{
		$channel = Mockery::mock('Rabbit_AMQP_Channel');
		
		try {
			$queue = new Rabbit_Queue($channel, null);
		} catch (Rabbit_Exception_Queue $e) {
			$this->assertEquals(Rabbit_Exception_Queue::ERROR_QUEUE_NAME_EMPTY, $e->getMessage());
		}
		
		/**
		 * I know this will be repeated in the other tests, but I rather have it here explicit than
		 * get it tested as a second effect.
		 */
		$channel->shouldReceive('queue_declare')->with(self::QUEUE_NAME, false, false, false, false);
		$queue = new Rabbit_Queue($channel, self::QUEUE_NAME, array(Rabbit_Connection::B_AMQP_DURABLE => false));
		
		$channel->shouldReceive('queue_declare')->with(self::QUEUE_NAME, false, true, false, false);
		$queue = new Rabbit_Queue($channel, self::QUEUE_NAME);
		
		$channel->shouldReceive('queue_declare')->with(self::QUEUE_NAME, true, true, false, false);
		$queue = new Rabbit_Queue($channel, self::QUEUE_NAME, array(Rabbit_Connection::B_AMQP_PASSIVE => true));
		
		$channel->shouldReceive('queue_declare')->with(self::QUEUE_NAME, false, true, true, false);
		$queue = new Rabbit_Queue($channel, self::QUEUE_NAME, array(Rabbit_Connection::B_AMQP_EXCLUSIVE => true));
		
		$channel->shouldReceive('queue_declare')->with(self::QUEUE_NAME, false, true, false, true);
		$queue = new Rabbit_Queue($channel, self::QUEUE_NAME, array(Rabbit_Connection::B_AMQP_AUTODELETE => true));
		
	}
	
	/**
	 * Tests the bind method.
	 * 
	 * @return void
	 */
	public function testBind()
	{
		$channel = $this->_getCommonChannelMock();
		
		$exchangeName = 'testExchange';
		$routingKey = 'testKey';
		
		$channel->shouldReceive('queue_bind')->with(self::QUEUE_NAME, $exchangeName, $routingKey);
		
		$queue = new Rabbit_Queue($channel, self::QUEUE_NAME);
		$queue->bind($exchangeName, $routingKey);
	}

	/**
	 * Tests the consume method.
	 * 
	 * @return void
	 */
	public function testConsume()
	{
		$consumerTag = 'test';
		
		$channel = $this->_getCommonChannelMock();
		/**
		 * One "callback" call. I know this is ugly and it's a hack, but
		 * Mockery doesn't give a way for mocking properties, yet.
		 */
		$channel->callbacks = new Rabbit_QueueTest_DecrementingCountable(1);
		
		$queue = new Rabbit_Queue($channel, self::QUEUE_NAME);
		
		// The idea here is not to test the callback.
		$callback = function() {};
		
		$channel->shouldReceive('basic_consume')->with(
			self::QUEUE_NAME, $consumerTag, false,
			false, false, false, $callback, $queue
		);
		
		
		$channel->shouldReceive('wait')->once();
		
		$queue->consume($callback, $consumerTag);
	}

	/**
	 * Generates a common {@link Rabbit_AMQP_Channel} mock for testing use.
	 * 
	 * @return \Mockery\MockInterface
	 */
	private function _getCommonChannelMock()
	{
		$channel = Mockery::mock('Rabbit_AMQP_Channel');
		$channel->shouldReceive('queue_declare')->with(self::QUEUE_NAME, false, true, false, false);
		
		return $channel;
	}
	
}