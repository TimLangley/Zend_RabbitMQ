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
class Rabbit_MessageTest extends PHPUnit_Framework_TestCase
{
    const QUEUE_NAME = 'test';

    /**
     * Tests the constructor method.
     *
     * @return void
     */
    public function testConstructor()
    {
        $msg = new Rabbit_Message('body', array('type' => 'test'));

        $this->assertEquals('body', $msg->body);
        $this->assertEquals('test', $msg->type);
    }

    /**
     * Tests the load_properties method.
     *
     * @return void
     */
    public function testLoadProperties()
    {
        $msg = new Rabbit_Message();
        $msg->load_properties(base64_decode('gIAQYXBwbGljYXRpb24vanNvbgEx'));

        $this->assertEquals('application/json', $msg->content_type);
        $this->assertEquals(1, $msg->message_id);
    }

    /**
     * Tests the serialize_properties method.
     *
     * @return void
     */
    public function testSerializeProperties()
    {
        $msg = new Rabbit_Message(
            '',
            array(
                'content_type' => 'application/json',
                'message_id' => 1
            )
        );

        $this->assertEquals(
            base64_decode('gIAQYXBwbGljYXRpb24vanNvbgEx'), // Binary data
            $msg->serialize_properties()
        );

    }

    /**
     * Tests the __set method.
     *
     * @return void
     */
    public function testMagicSet()
    {
        $msg = new Rabbit_Message();
        $msg->delivery_info = 'delivery';
        $msg->body = 'body';

        $this->assertEquals('delivery', $msg->delivery_info);
        $this->assertEquals('body', $msg->body);

        $caught = false;
        try {
            $msg->nonexistant = 1;
        } catch (Rabbit_Exception_Message $e) {
            $caught = true;

            $this->assertEquals(
                sprintf(
                    Rabbit_Exception_Message::ERROR_NO_PROPERTY,
                    'nonexistant'
                ),
                $e->getMessage()
            );
        }

        $this->assertTrue($caught);
    }
}