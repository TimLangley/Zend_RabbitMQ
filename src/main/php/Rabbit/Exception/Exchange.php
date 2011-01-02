<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */
class RABBIT_Exception_Exchange extends RABBIT_Exception {
	const	ERROR_CHANNEL_EMPTY			= "Channel can't be null - use RABBIT_Connection->getExchange()";
	const	ERROR_EXCHANGE_NAME_EMPTY	= "Exchange name can't be empty";
	const	ERROR_UNKNOWN_EXCHANGE_TYPE	= "Exchange Type %s isn't recognised";
}

