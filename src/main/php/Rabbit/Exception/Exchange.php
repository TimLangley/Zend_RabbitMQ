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
 * Exception for rabbit's exchanges errors.
 */
class Rabbit_Exception_Exchange extends Rabbit_Exception
{
    const ERROR_CHANNEL_EMPTY = 
        'Channel can\'t be null - use Rabbit_Connection->getExchange()';
    const ERROR_EXCHANGE_NAME_EMPTY = 'Exchange name can\'t be empty';
    const ERROR_UNKNOWN_EXCHANGE_TYPE = 'Exchange Type %s isn\'t recognised';
}

