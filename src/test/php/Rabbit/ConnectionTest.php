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
class Rabbit_ConnectionTest extends PHPUnit_Framework_TestCase
{

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
     * Tests the getQueue method.
     *
     * @return void
     */
    public function testGetQueue()
    {
        $channel = Mockery::mock('Rabbit_AMQP_Channel');
        $channel->shouldReceive('access_request')->with(
            '/', false, false, true, false
        );

        $channel->shouldReceive('queue_declare')->withAnyArgs();

        $amqpConnection = Mockery::mock('Rabbit_AMQP_Connection');
        $amqpConnection->shouldReceive('channel')->andReturn($channel);

        // FIXME: I don't understand why without this an exception is raised..
        $amqpConnection->sock = false;

        $connection = new Rabbit_Connection();
        $connection->setConnection($amqpConnection);

        $this->assertTrue(
            $connection->getQueue('test') instanceof Rabbit_Queue
        );
    }

    /**
     * Tests the getExchange method.
     *
     * @return void
     */
    public function testGetExchange()
    {
        $channel = Mockery::mock('Rabbit_AMQP_Channel');
        $channel->shouldReceive('access_request')->with(
            '/', false, false, true, true
        );

        $channel->shouldReceive('exchange_declare')->withAnyArgs();

        $amqpConnection = Mockery::mock('Rabbit_AMQP_Connection');
        $amqpConnection->shouldReceive('channel')->andReturn($channel);

        // FIXME: I don't understand why without this an exception is raised..
        $amqpConnection->sock = false;

        $connection = new Rabbit_Connection();
        $connection->setConnection($amqpConnection);

        $this->assertTrue($connection->getExchange('test') instanceof Rabbit_Exchange);

    }

}