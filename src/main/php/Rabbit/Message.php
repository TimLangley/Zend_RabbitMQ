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

class Rabbit_Message
{
    const MESSAGE_CONSUME_CANCEL = 'quit';

    protected static $_arrProperyTypes = array(
       'content_type'             => 'shortstr',
        'content_encoding'         => 'shortstr',
        'application_headers'     => 'table',
        'delivery_mode'         => 'octet',
        'priority'                 => 'octet',
        'correlation_id'         => 'shortstr',
        'reply_to'                 => 'shortstr',
        'expiration'             => 'shortstr',
        'message_id'             => 'shortstr',
        'timestamp'             => 'timestamp',
        'type'                     => 'shortstr',
        'user_id'                 => 'shortstr',
        'app_id'                 => 'shortstr',
        'cluster_id'             => 'shortstr'
    );

    private $_body;

    /**
     * @var array
     */
    private $_properties;

    /**
     * @var array
     */
    private $_deliveryInfo;

    /**
     * Creates a new message with the given body and properties.
     *
     * @param string $body  The message's body.
     * @param array  $props The message's properties.
     */
    public function __construct($body = '', $props = array())
    {
        // TODO: Does really makes sense to have that $body as optional?

        $this->_body = $body;

        if (!empty($props)) {
            $props = array_intersect_key($props, self::$_arrProperyTypes);
        }

        $this->_properties = $props;
    }

    /**
     * Retrieves the message's properties.
     *
     * @param string $name The property name.
     *
     * @return mixed
     *
     * @throws Rabbit_Exception_Message
     */
    public function __get($name)
    {
        /**
          * Look for additional properties in the 'properties' dictionary,
         * and if present - the 'delivery_info' dictionary.
         */
        switch($name) {
            case 'body':
                return $this->_body;

            case 'delivery_info':
                return $this->_deliveryInfo;

            case 'delivery_tag':
                if (isset($this->_deliveryInfo)) {
                    if (array_key_exists(
                        'delivery_tag',
                        $this->_deliveryInfo
                    )) {
                        return $this->_deliveryInfo['delivery_tag'];
                    }

                    throw new Rabbit_Exception_Message(
                        sprintf(
                            Rabbit_Exception_Message::ERROR_NO_PROPERTY,
                            $name
                        )
                    );
                }

            default:
                if (array_key_exists($name, $this->_properties)) {
                    return $this->_properties[$name];
                }

                if (isset($this->_deliveryInfo)) {
                    if (array_key_exists($name, $this->_deliveryInfo)) {
                        return $this->_deliveryInfo[$name];
                    }
                }

                throw new Rabbit_Exception_Message(
                    sprintf(
                        Rabbit_Exception_Message::ERROR_NO_PROPERTY,
                        $name
                    )
                );
        }
    }

    /**
     * Sets a message's property.
     *
     * @param string $name  The property's name.
     * @param string $value The new property's value.
     *
     * @throws Rabbit_Exception_Message
     *
     * @return void
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'body':
                $this->_body = $value;
                break;
            case 'delivery_info':
                $this->_deliveryInfo = $value;
                break;
            default:
                throw new Rabbit_Exception_Message(
                    sprintf(
                        Rabbit_Exception_Message::ERROR_NO_PROPERTY,
                        $name
                    )
                );
        }
    }

    /**
     * Parses properties.
     *
     * Given the raw bytes containing the property-flags and
     * property-list from a content-frame-header, parse and insert
     * into a dictionary stored in this object as an attribute named
     * 'properties'.
     *
     * @param array $raw_bytes The bytes to parse.
     *
     * @return void
     */
    public function load_properties($raw_bytes)
    {
        $r = new Rabbit_AMQP_Serialize_Read($raw_bytes);

        // Read 16-bit shorts until we get one with a low bit set to zero
        $flags = array();

        while (true) {
            $flag_bits = $r->read_short();
            array_push($flags, $flag_bits);

            if (($flag_bits & 1) == 0) {
                break;
            }
        }

        $shift = 0;
        $d = array();

        foreach (self::$_arrProperyTypes as $key => $proptype) {
            // TODO: Should this be compared by identity? (===)
            if (0 == $shift) {
                if (!$flags) {
                    break;
                }

                $flag_bits = array_shift($flags);
                $shift = 15;
            }

            if ($flag_bits & (1 << $shift)) {
                $d[$key] = call_user_func(array($r, "read_" . $proptype));
            }

            $shift -= 1;
        }

        $this->_properties     = $d;
    }

    /**
     * Serializes the 'properties' attribute (a dictionary) into raw bytes.
     *
     * This makes a set of property flags and a property list, suitable for
     * putting into a content frame header.
     *
     * @return mixed
     */
    public function serialize_properties()
    {
        $shift     = 15;
        $flag_bits = 0;
        $flags     = array();
        $raw_bytes = new Rabbit_AMQP_Serialize_Write();

        foreach (self::$_arrProperyTypes as $key => $proptype) {
            $val = null;

            if (array_key_exists($key, $this->_properties)) {
               $val = $this->_properties[$key];
            }

            if (!is_null($val)) {
                if ($shift == 0) {
                    array_push($flags, $flag_bits);
                    $flag_bits = 0;
                    $shift = 15;
                }

                $flag_bits |= (1 << $shift);

                if ($proptype != 'bit') {
                    call_user_func(
                        array($raw_bytes, "write_" . $proptype),
                        $val
                    );
                }
            }

            $shift -= 1;
        }

        array_push($flags, $flag_bits);
        $result = new Rabbit_AMQP_Serialize_Write();

        foreach ($flags as $flag_bits) {
            $result->write_short($flag_bits);
        }

        $result->write($raw_bytes->getvalue());

        return $result->getvalue();
    }

}