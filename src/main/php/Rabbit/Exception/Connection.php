<?php
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

/**
 * Exception thrown when a connection error occurs.
 */
class Rabbit_Exception_Connection extends Rabbit_Exception
{
    const ERROR_NOT_CONNECTED = 'Must be connected';
}

