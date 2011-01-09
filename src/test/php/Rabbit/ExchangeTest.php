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
class Rabbit_ExchangeTest extends PHPUnit_Framework_TestCase
{
    
    const EXCHANGE_NAME = 'test';
    const EXCHANGE_TYPE = Rabbit_Exchange::EXCHANGE_TYPE_FANOUT;
    
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
     * Tests the constructor method.
     * 
     * @return void
     */
    public function testConstruct()
    {
        $channel = $this->_getCommonChannelMock();
        
        // Since php doesn't have finally we have to do this by hand.
        $caught = false;
        
        try {
            $exchange = new Rabbit_Exchange($channel, null, null);
        } catch (Rabbit_Exception_Exchange $e) {
            $this->assertEquals(
                $e->getMessage(),
                Rabbit_Exception_Exchange::ERROR_EXCHANGE_NAME_EMPTY
            );
            
            $caught = true;
        }
        
        $this->assertTrue($caught, 'Exception didn\'t rise.');
        
        $caught = false;
        try {
            $exchange = new Rabbit_Exchange(
                $channel, self::EXCHANGE_NAME, 'foo'
            );
        } catch (Rabbit_Exception_Exchange $e) {
            $this->assertEquals(
                $e->getMessage(),
                sprintf(
                    Rabbit_Exception_Exchange::ERROR_UNKNOWN_EXCHANGE_TYPE,
                    'foo'
                )
            );
            
            $caught = true;
        }
        
        $this->assertTrue($caught, 'Exception didn\'t rise.');
        
        // Create flag object.
        $exchange = new Rabbit_Exchange(
            $channel, self::EXCHANGE_NAME, self::EXCHANGE_TYPE
        );
        
        $flags = $this->_getCommonFlagsMock();
        $flags->shouldReceive('getPassive')->andReturn(true);
        
        $channel->shouldReceive('exchange_declare')->with(
            self::EXCHANGE_NAME,
            self::EXCHANGE_TYPE,
            true,
            true,
            false
        );
        
        // Use flag object provided.
        $exchange = new Rabbit_Exchange(
            $channel, self::EXCHANGE_NAME, self::EXCHANGE_TYPE
        );
        
        $channel->shouldReceive('exchange_declare')->with(
            self::EXCHANGE_NAME,
            Rabbit_Exchange::EXCHANGE_TYPE_DIRECT,
            false,
            true,
            false
        );
        
        // Default to direct type.
        $exchange = new Rabbit_Exchange(
            $channel, self::EXCHANGE_NAME, null
        );
        
    }

    /**
     * Tests the bind method.
     * 
     * @return void
     */
    public function testBind()
    {
        $queueName = 'test';
        $routingKey = '*';
        
        $channel = $this->_getCommonChannelMock();
        $channel->shouldReceive('queue_bind')->with(
            $queueName, self::EXCHANGE_NAME, $routingKey
        );
        
        $exchange = new Rabbit_Exchange(
            $channel, self::EXCHANGE_NAME, self::EXCHANGE_TYPE
        );
        
        $exchange->bind($queueName, $routingKey);
    }
    
    /**
     * Tests the delete method.
     * 
     * @return void
     */
    public function testDelete()
    {
        $channel = $this->_getCommonChannelMock();
        $channel->shouldReceive('exchange_delete')->with(self::EXCHANGE_NAME);
        
        $exchange = new Rabbit_Exchange(
            $channel, self::EXCHANGE_NAME, self::EXCHANGE_TYPE
        );
        
        $exchange->delete();
        
    }

    /**
     * Tests the publish method.
     * 
     * @return void
     */
    public function testPublish()
    {
        $routingKey = '*';
        $message = Mockery::mock('Rabbit_Message');
        
        $channel = $this->_getCommonChannelMock();
        $channel->shouldReceive('basic_publish')->with(
            $message, self::EXCHANGE_NAME, $routingKey
        );
        
        $exchange = new Rabbit_Exchange(
            $channel, self::EXCHANGE_NAME, self::EXCHANGE_TYPE
        );
        
        $exchange->publish($message, $routingKey);
    }
    
    /**
     * Generates a common {@link Rabbit_AMQP_Channel} mock for testing use.
     * 
     * @return \Mockery\MockInterface
     */
    private function _getCommonChannelMock()
    {
        $channel = Mockery::mock('Rabbit_AMQP_Channel');
        $channel->shouldReceive('exchange_declare')->with(
            self::EXCHANGE_NAME,
            self::EXCHANGE_TYPE,
            false,
            true,
            false
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