<?php
/**
 * @category   
 * @package    
 * @copyright  2011-01-02, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

/**
 * Exception for rabbit's messages errors.
 */
class Rabbit_Exception_Message extends Rabbit_Exception
{
    const ERROR_NO_PROPERTY= 'No such property: %s';
}
