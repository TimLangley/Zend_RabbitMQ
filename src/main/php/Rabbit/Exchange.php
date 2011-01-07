<?php
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

/**
 * Represents a rabbit exchange.
 */
class Rabbit_Exchange
{
    const EXCHANGE_TYPE_DIRECT = 'direct';
    const EXCHANGE_TYPE_FANOUT = 'fanout';
    const EXCHANGE_TYPE_TOPIC  = 'topic';
    
    private static $_arrExchangeTypes = array(
        self::EXCHANGE_TYPE_DIRECT => 1,
        self::EXCHANGE_TYPE_FANOUT => 1,
        self::EXCHANGE_TYPE_TOPIC  => 1
    );
    
    private $_exchangeName;
    private $_exchangeType;
    private $_amqpChannel;
    
    /**
     * Creates a new Exchange or loads an existing one.
     *    
     * This is a bit ugly - because whilst this is a public constructor
     * it's not possible to create these directly because can't get access
     * to the Channel (outside of the Rabbit_Connection)
     * 
     * @param Rabbit_AMQP_Channel $amqpChannel The channel to use. 
     * @param string              $name        The Exchange Name
     * @param string              $type        The type of Exchange one of: 
     *                        {@link Rabbit_Exchange::EXCHANGE_TYPE_DIRECT}
     *                        {@link Rabbit_Exchange::EXCHANGE_TYPE_FANOUT}
     *                        {@link Rabbit_Exchange::EXCHANGE_TYPE_TOPIC}
     * @param Rabbit_Flags        $flags       Flags object.
     *
     * @throws Rabbit_Exception_Exchange
     * @see Rabbit_Flags
     */
    public function __construct(Rabbit_AMQP_Channel $amqpChannel, $name, $type,
        Rabbit_Flags $flags = null)
    {
        
        if (empty($name)) {
            throw new Rabbit_Exception_Exchange(
                Rabbit_Exception_Exchange::ERROR_EXCHANGE_NAME_EMPTY
            );
        }
        
        // TODO: This should throw an exception, not go with the default value.
        if (is_null($type)) {
            $type = self::EXCHANGE_TYPE_DIRECT;
        }
        
        if (is_null($flags)) {
            $flags = new Rabbit_Flags();
        }
        
        if (!array_key_exists($type, self::$_arrExchangeTypes)) {
            throw new Rabbit_Exception_Exchange(
                sprintf(
                    Rabbit_Exception_Exchange::ERROR_UNKNOWN_EXCHANGE_TYPE,
                    $type
                )
            );
        }
            
        $this->_exchangeName    = $name;
        $this->_exchangeType    = $type;
        $this->_amqpChannel     = $amqpChannel;
        
        $this->_amqpChannel->exchange_declare(
            $this->_exchangeName,
            $this->_exchangeType,
            $flags->getPassive(),
            $flags->getDurable(), 
            $flags->getAutodelete()
        );
        
    }
    
    /**
     * Binds a new Queue to this exchange.
     * 
     * @param string $queueName  The queue's name.
     * @param string $routingKey The routing key to use.
     * 
     * @return void
     */
    public function bind($queueName, $routingKey = null)
    {
        $this->_amqpChannel->queue_bind(
            $queueName, $this->_exchangeName, $routingKey
        );
    }
    
    /**
     * Deletes this exchange.
     * 
     * @return void
     */
    public function delete()
    {
        $this->_amqpChannel->exchange_delete($this->_exchangeName);
    }
    
    /**
     * Publishes the given message to this exchange.
     * 
     * @param Rabbit_Message $message    The message to publish.
     * @param string         $routingKey The routing key to use.
     * 
     * @return void
     */
    public function publish(Rabbit_Message $message, $routingKey = null)
    {
        $this->_amqpChannel->basic_publish(
            $message, $this->_exchangeName, $routingKey
        );
    }
    
}