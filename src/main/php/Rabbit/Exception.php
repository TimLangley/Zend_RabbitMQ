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
 * Rabbit generic exception.
 */
class Rabbit_Exception extends Exception
{
    const ERROR_CONNECTING = 'Error Connecting to server(%d): %s';
    const ERROR_SERIALIZE_BC_MATH = '\'bc math\' module required';
    const ERROR_SERIALIZE_EXPONENT = 'Decimal exponent value must be unsigned!';
    const ERROR_SERIALIZE_NOT_INTEGER = 'Octet out of range 0..65535';
    const ERROR_SERIALIZE_NOT_OCTAL = 'Octet out of range 0..255';
    const ERROR_SERIALIZE_NOT_ZERO = 'Value too big!';
    const ERROR_SERIALIZE_STRING_TOO_LONG = 'String too long';
    const ERROR_SERIALIZE_TABLE = 'Table is longer than supported';
    const ERROR_SERIALIZE_READING =
        'Error reading data. Recevived %d instead of expected %d bytes';
}