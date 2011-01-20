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
class Rabbit_FlagsTest extends PHPUnit_Framework_TestCase
{

    /**
     * Super test that tests the class entirely.
     *
     * @return void
     */
    public function testAll()
    {
        $flags = new Rabbit_Flags(
            array(
                Rabbit_Flags::B_AMQP_ACTIVE => true,
                Rabbit_Flags::B_AMQP_AUTODELETE => false,
                Rabbit_Flags::B_AMQP_DURABLE => true,
                Rabbit_Flags::B_AMQP_EXCLUSIVE => false,
                Rabbit_Flags::B_AMQP_PASSIVE => true
            )
        );

        $this->assertTrue($flags->getActive());
        $this->assertFalse($flags->getAutodelete());
        $this->assertTrue($flags->getDurable());
        $this->assertFalse($flags->getExclusive());
        $this->assertTrue($flags->getPassive());

    }

}