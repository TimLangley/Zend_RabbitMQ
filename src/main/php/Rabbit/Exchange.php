<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class RABBIT_Exchange															{
	const	EXCHANGE_TYPE_DIRECT		= "direct";
	const	EXCHANGE_TYPE_FANOUT		= "fanout";
	const	EXCHANGE_TYPE_TOPIC			= "topic";
	
	private static $_arrExchangeTypes	= array(	self::EXCHANGE_TYPE_DIRECT	=> 1
												,	self::EXCHANGE_TYPE_FANOUT	=> 1
												,	self::EXCHANGE_TYPE_TOPIC	=> 1);
	
	private $_strExchangeName;
	private $_strExchangeType;
	private $_amqpChannel;
	
	public function __construct(	RABBIT_AMQP_Channel $amqpChannel
								,	$strName						= null
								, 	$strType 						= null
								, 	$flags 							= null)		{
		/**
		 *	@purpose:	This is used to create a new Exchange (or to load an existing one)
		 *	@NOTE:		This is a bit ugly - because whilst this is a public constructor
		 *					it's not possible to create these directly because can't get access to the Channel 
		 *					(outside of the RABBIT_Connection)
		 */
		if(is_null($strName))
			throw new RABBIT_Exception_Exchange(RABBIT_Exception_Exchange::ERROR_EXCHANGE_NAME_EMPTY);
		if(is_null($amqpChannel))
			throw new RABBIT_Exception_Exchange(RABBIT_Exception_Exchange::ERROR_CHANNEL_EMPTY);
		if(is_null($strType))
			$strType			= self::EXCHANGE_TYPE_DIRECT;
		if(!array_key_exists($strType, self::$_arrExchangeTypes))
			throw new RABBIT_Exception_Exchange(sprintf(RABBIT_Exception_Exchange::ERROR_UNKNOWN_EXCHANGE_TYPE, $strType));
			
		$this->_strExchangeName	= $strName;
		$this->_strExchangeType	= $strType;
		$this->_amqpChannel		= $amqpChannel;
		
		//@TODO - move these booleans into the $flags
		$this->_amqpChannel->exchange_declare($this->_strExchangeName, $this->_strExchangeType, false, true, false);
								}
	public function bind($strQueueName, $strRoutingKey = null)					{
		/**
		 *	@purpose:	Bind a new Queue to this exchange
		 */
		$this->_amqpChannel->queue_bind($strQueueName, $this->_strExchangeName, $strRoutingKey);
	}
	public function delete()													{
		/**
		 *	@purpose:	Delete this exchange
		 */
		$this->_amqpChannel->exchange_delete($this->_strExchangeName);
	}
	public function publish ( RABBIT_Message $message
							, $strRoutingKey = null)							{
		/**
		 *	@purpose:	Publishes the message to this exchange
		 */
		$this->_amqpChannel->basic_publish($message, $this->_strExchangeName, $strRoutingKey);
							}
}