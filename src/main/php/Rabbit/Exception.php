<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class Rabbit_Exception extends Exception {
	const	ERROR_CONNECTING				= "Error Connecting to server(%d): %s";
	const 	ERROR_SERIALIZE_BC_MATH			= "'bc math' module required";
	const 	ERROR_SERIALIZE_EXPONENT		= "Decimal exponent value must be unsigned!";
	const	ERROR_SERIALIZE_NOT_INTEGER		= 'Octet out of range 0..65535';
	const 	ERROR_SERIALIZE_NOT_OCTAL		= 'Octet out of range 0..255';
	const 	ERROR_SERIALIZE_NOT_ZERO		= "Value too big!";
	const	ERROR_SERIALIZE_READING			= "Error reading data. Recevived %d instead of expected %d bytes";
	const	ERROR_SERIALIZE_STRING_TOO_LONG	= 'String too long';
	const	ERROR_SERIALIZE_TABLE			= "Table is longer than supported";
}