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
**/

/**
 * @category
 * @package
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license
 * @author     Tim Langley
**/

/**
 * Represents a rabbit exchange.
**/
class Rabbit_Exchange
{
    const EXCHANGE_TYPE_DIRECT = 'direct';
    const EXCHANGE_TYPE_FANOUT = 'fanout';
    const EXCHANGE_TYPE_TOPIC  = 'topic';

    private static $_arrExchangeTypes = array(
        self::EXCHANGE_TYPE_DIRECT,
        self::EXCHANGE_TYPE_FANOUT,
        self::EXCHANGE_TYPE_TOPIC
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
    **/
    public function __construct(Rabbit_AMQP_Channel $amqpChannel, $name, $type,
        Rabbit_Flags $flags = null)
    {

        if (empty($name)) {
            throw new Rabbit_Exception_Exchange(
                Rabbit_Exception_Exchange::ERROR_EXCHANGE_NAME_EMPTY
            );
        }

        if (is_null($type)) {
            $type = self::EXCHANGE_TYPE_DIRECT;
        } else {
            if (false === array_search($type, self::$_arrExchangeTypes)) {
                throw new Rabbit_Exception_Exchange(
                    sprintf(
                        Rabbit_Exception_Exchange::ERROR_UNKNOWN_EXCHANGE_TYPE,
                        $type
                    )
                );
            }
        }

        if (is_null($flags)) {
            $flags = new Rabbit_Flags();
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
    **/
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
    **/
    public function delete()
    {
        $this->_amqpChannel->exchange_delete($this->_exchangeName);
    }
	
	/**
     * Returns the Exchange Name
     *
     * @return string
    **/
	public function getExchangeName()
	{
		return $this->_exchangeName;
	}

	/**
     * Returns the Exchange Type
     *
     * @return string
    **/
	public function getExchangeType()
	{
		return $this->_exchangeType;
	}

    /**
     * Publishes the given message to this exchange.
     *
     * @param Rabbit_Message $message    The message to publish.
     * @param string         $routingKey The routing key to use.
     *
     * @return void
    **/
    public function publish(Rabbit_Message $message, $routingKey = null)
    {
        $this->_amqpChannel->basic_publish(
            $message, $this->_exchangeName, $routingKey
        );
    }

}