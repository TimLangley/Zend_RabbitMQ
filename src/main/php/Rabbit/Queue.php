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
 * @category
 * @package
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license
 * @author     Tim Langley
 */

/**
 * Represents a rabbit's queue.
 */
class Rabbit_Queue
{

    /**
     * @var Rabbit_AMQP_Channel
     */
    private $_amqpChannel;

    private $_queueName;
    private $_consumerTag;

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
     * @param Rabbit_Flags        $flags       Flags object.
     *
     * @throws Rabbit_Exception_Queue
     * @see Rabbit_Flags
     */
    public function __construct(Rabbit_AMQP_Channel $amqpChannel, $queueName,
        Rabbit_Flags $flags)
    {

        if (empty($queueName)) {
            throw new Rabbit_Exception_Queue(
                Rabbit_Exception_Queue::ERROR_QUEUE_NAME_EMPTY
            );
        }

        $this->_queueName   = $queueName;
        $this->_amqpChannel = $amqpChannel;

        $this->_amqpChannel->queue_declare(
            $this->_queueName,
            $flags->getPassive(),
            $flags->getDurable(),
            $flags->getExclusive(),
            $flags->getAutodelete()
        );

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
     * @param Closure $callback    The callback to use.
     * @param string  $consumerTag The tag used for the consumer.
     *
     * @return void
     */
    public function consume(Closure $callback, $consumerTag)
    {
        $this->_consumerTag = $consumerTag;

        // FIXME: I suspect this is where the circular reference occurs ;)
        $this->_amqpChannel->basic_consume(
            $this->_queueName,
            $consumerTag,
            false,
            false,
            false,
            false,
            $callback
        );

        // Loop as long as the channel has callbacks registered.
        while (count($this->_amqpChannel->callbacks)) {
            $this->_amqpChannel->wait();
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
