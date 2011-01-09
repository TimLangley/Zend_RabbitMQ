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
        $channel = $this->_getCommonChannelMock();
        
        // Default ones.
        $flags = $this->_getCommonFlagsMock();
        
        try {
            $queue = new Rabbit_Queue($channel, null, $flags);
        } catch (Rabbit_Exception_Queue $e) {
            $this->assertEquals(
                Rabbit_Exception_Queue::ERROR_QUEUE_NAME_EMPTY, $e->getMessage()
            );
        }
        
        $queue = new Rabbit_Queue(
            $channel, self::QUEUE_NAME, $flags
        );
        
        $channel->mockery_verify();
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
        
        $channel->shouldReceive('queue_bind')->with(
            self::QUEUE_NAME, $exchangeName, $routingKey
        );

        $flags = $this->_getCommonFlagsMock();
         
        $queue = new Rabbit_Queue(
            $channel, self::QUEUE_NAME, $this->_getCommonFlagsMock()
        );
        
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
        
        $queue = new Rabbit_Queue(
            $channel, self::QUEUE_NAME, $this->_getCommonFlagsMock()
        );
        
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
     * Tests the consume_cancel method.
     * 
     * @return void
     */
    public function testConsumeCancel()
    {
        $channel = $this->_getCommonChannelMock();
        
        $queue = new Rabbit_Queue(
            $channel, self::QUEUE_NAME, $this->_getCommonFlagsMock()
        );

        /**
         * This could be improved, since the null value comes from an private
         * variable that is initialized when consume() is called, its value
         * should change. But testing that would require more effort than the
         * what I'm willing to put in this. This is okay for now.
         */
        $channel->shouldReceive('basic_cancel')->with(null)->once();
        
        $queue->consume_cancel();
    }

    /**
     * Tests the delete method.
     * 
     * @return void
     */
    public function testDelete()
    {
        $channel = $this->_getCommonChannelMock();
        $queue = new Rabbit_Queue(
            $channel, self::QUEUE_NAME, $this->_getCommonFlagsMock()
        );
        
        $channel->shouldReceive('queue_delete')->with(self::QUEUE_NAME);
        
        $queue->delete();
    }

    /**
     * Tests the get method.
     * 
     * @return void
     */
    public function testGet()
    {
        $channel = $this->_getCommonChannelMock();
        
        $queue = new Rabbit_Queue(
            $channel, self::QUEUE_NAME, $this->_getCommonFlagsMock()
        );
        
        $channel->shouldReceive('basic_get')->with(self::QUEUE_NAME)->andReturn(123);
        
        $this->assertEquals(123, $queue->get());
    }

    /**
     * Tests the purge method.
     * 
     * @return void
     */
    public function testPurge()
    {
        $channel = $this->_getCommonChannelMock();
        $queue = new Rabbit_Queue(
            $channel, self::QUEUE_NAME, $this->_getCommonFlagsMock()
        );
        
        $channel->shouldReceive('queue_purge')->with(self::QUEUE_NAME);
        
        $queue->purge();
    }
    
    /**
     * Generates a common {@link Rabbit_AMQP_Channel} mock for testing use.
     * 
     * @return \Mockery\MockInterface
     */
    private function _getCommonChannelMock()
    {
        $channel = Mockery::mock('Rabbit_AMQP_Channel');
        $channel->shouldReceive('queue_declare')->with(
            self::QUEUE_NAME, false, true, false, false
        )->atLeast()->times(1);
        
        return $channel;
    }
    
    /**
     * Generates a {@link Rabbit_Flags} common mock, with default flags set.
     * 
     * @return Rabbit_Flags
     */
    private function _getCommonFlagsMock()
    {
        $mock = Mockery::mock('Rabbit_Flags');
        
        $mock->shouldReceive('getPassive')
            ->andReturn(false)->zeroOrMoreTimes();
            
        $mock->shouldReceive('getDurable')
            ->andReturn(true)->zeroOrMoreTimes();
            
        $mock->shouldReceive('getAutodelete')
            ->andReturn(false)->zeroOrMoreTimes();
            
        $mock->shouldReceive('getExclusive')
            ->andReturn(false)->zeroOrMoreTimes();
            
        $mock->shouldReceive('getActive')
            ->andReturn(true)->zeroOrMoreTimes();
            
        return $mock;
    }
    
}