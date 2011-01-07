<?php
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */
class Rabbit_Exception_Queue extends Rabbit_Exception
{
    const ERROR_CHANNEL_EMPTY = 'Channel can\'t be null - use Rabbit_Connection->getQueue()';
    const ERROR_QUEUE_NAME_EMPTY = 'Queue name can\'t be empty';
}