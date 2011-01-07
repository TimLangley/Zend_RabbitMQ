<?php
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class Rabbit_Queue                                                                
{
    
    /**
     * @var Rabbit_AMQP_Channel
     */
    private $_amqpChannel;
    
    private $_queueName;
    private $_consumerTag;
    
    // FIXME: This constant should be at Rabbit_Message
    const MESSAGE_CONSUME_CANCEL = 'quit';
    
    /**
     * This loads a new Rabbit_Queue (or creates one)
     * 
     * This is a bit ugly - because whilst this is a public constructor
     * it's not possible to create these directly because can't get access to
     * the Channel (outside of the Rabbit_Connection)
     *                    
     * @param Rabbit_AMQP_Channel $amqpChannel The AMQP channel to connect
     *                                         through.
     * @param string              $queueName   The Queue Name
     * @param boolean             $passive     {@link Rabbit_Connection::B_AMQP_PASSIVE} 
     * @param boolean             $durable     {@link Rabbit_Connection::B_AMQP_DURABLE}
     * @param boolean             $exclusive   {@link Rabbit_Connection::B_AMPQ_EXCLUSIVE}
     * @param boolean             $autoDelete  {@link Rabbit_Connection::B_AMPQ_AUTODELETE}
     * 
     * @throws Rabbit_Queue_Exception
     */
    public function __construct(Rabbit_AMQP_Channel $amqpChannel, $queueName,
        $passive, $durable, $exclusive, $autoDelete)
    {
        
        if (is_null($queueName)) {
            throw new Rabbit_Exception_Queue(
                Rabbit_Exception_Queue::ERROR_QUEUE_NAME_EMPTY
            );
        }
            
        $this->_queueName   = $queueName;    
        $this->_amqpChannel = $amqpChannel;
        
        $this->_amqpChannel->queue_declare(
            $this->_queueName, $passive, $durable, $exclusive, $autoDelete
        );
        
    }
    
    /**
     * This performs the message acknowledgement.
     *  
     * @param Rabbit_Message $msg The message to acknowledge.
     * @return void
     */
    private function _ack(Rabbit_Message $msg)
    {
        $this->_amqpChannel->basic_ack($msg->delivery_tag);
    }
    
    /**
     * Binds this Queue to the given Exchange with the given RoutingKey
     * 
     * If you bind a "second" consumer to the same queue (ie queue name is the
     * same then routing is ignored) and this is a "round robin" scenario
     * 
     * @param string $exchangeName The exchange's name.        
     * @param string $routingKey   The routing key to use.
     * 
     * @return void
     */
    public function bind($exchangeName, $routingKey = null)
    {
        $this->_amqpChannel->queue_bind(
            $this->_queueName, $exchangeName, $routingKey
        );
    }
    
    /**
     * This "sits" consuming the messages.
     * 
     * When one arrives it calls the given callback.
     * 
     * @param Closure $callback The callback to use.
     * 
     * @return void
     */
    public function consume($callback, $consumerTag, $arrOptions = null)
    {
        // FIXME: Use a Closure for the callback!!
        
        $this->_consumerTag = $consumerTag;
        
        // FIXME: I suspect this is where the circular reference occurs ;)
        $this->_amqpChannel->basic_consume(
            $this->_queueName,
            $consumerTag,
            false,
            false,
            false,
            false,
            $callback,
            $this
        );

        // Loop as long as the channel has callbacks registered.
        while (count($this->_amqpChannel->callbacks)) {
            $this->_amqpChannel->wait();
        }
        
    }
    
    /**
     * This is the "generic callback" which is called by the Channel.
     */
    public final function _consume_cb(Rabbit_Message $msg, $fnUserCallback = null)
    {
        $this->_ack($msg);

        // Cancel callback
        if (self::MESSAGE_CONSUME_CANCEL === $msg->body)  {
            return $this->consume_cancel();
        }
        
        if (!is_null($fnUserCallback)) {
            $fnUserCallback($msg);
        }
    }
    
    /**
     * Cancels a consume call.
     * 
     * @return void
     */
    public function consume_cancel()
    {
        $this->_amqpChannel->basic_cancel($this->_consumerTag);
    }
    
    /**
     * Deletes the current queue.
     * 
     * @return void
     */
    public function delete()
    {
        $this->_amqpChannel->queue_delete($this->_queueName);
    }
    
    /**
     * Gets... something?
     * 
     * @return mixed
     */
    public function get($arrFlags = null)
    {
        return $this->_amqpChannel->basic_get($this->_queueName);
    }
    
    /**
     * Clears the queue of any outstanding messages.
     * 
     * @return void
     */
    public function purge()
    {
        $this->_amqpChannel->queue_purge($this->_queueName);
    }
    
}
