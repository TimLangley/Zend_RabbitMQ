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
								, 	$arrFlags 						= null)		{
		/**
		 *	@purpose:	This is used to create a new Exchange (or to load an existing one)
		 *	@NOTE:		This is a bit ugly - because whilst this is a public constructor
		 *					it's not possible to create these directly because can't get access to the Channel 
		 *					(outside of the RABBIT_Connection)
		 *	@param:		strName		The Exchange Name
		 *	@param:		strType		The Exchange Type (taken from RABBIT_Exchange::EXCHANGE_TYPE_)
		 *	@param:		arrFlags	Associative array of flags
		 *								"B_AMQP_PASSIVE"	=> Check if Exchange exists
		 *														Passive exchanges are queues will not be redeclared,
		 *														the broker will throw an error if the exchange does not exist.
		 *								"B_AMQP_DURABLE"	=> Durable exchanges and queues will survive a broker restart,
		 *														complete with all of their data.
		 *								"B_AMQP_AUTODELETE"	=> For exchanges, the auto delete flag indicates that the exchange will 
		 *														be deleted as soon as no more queues are bound to it. 
		 *														If no queues were ever bound the exchange, 
		 *														the exchange will never be deleted
		 */
		
		if(is_null($strName))
			throw new RABBIT_Exception_Exchange(RABBIT_Exception_Exchange::ERROR_EXCHANGE_NAME_EMPTY);
		if(is_null($amqpChannel))
			throw new RABBIT_Exception_Exchange(RABBIT_Exception_Exchange::ERROR_CHANNEL_EMPTY);
		if(is_null($strType))
			$strType			= self::EXCHANGE_TYPE_DIRECT;
		if(!array_key_exists($strType, self::$_arrExchangeTypes))
			throw new RABBIT_Exception_Exchange(sprintf(RABBIT_Exception_Exchange::ERROR_UNKNOWN_EXCHANGE_TYPE, $strType));
		
		$bPassive				= false;
		$bDurable				= true;
		$bAutoDelete			= false;
		if(!is_array($arrFlags))												{
			if(array_key_exists(RABBIT_Connection::B_AMQP_PASSIVE, 		$arrFlags))
				$bPassive		= $arrFlags[RABBIT_Connection::B_AMQP_PASSIVE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_DURABLE, 		$arrFlags))
				$bDurable		= $arrFlags[RABBIT_Connection::B_AMQP_DURABLE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_AUTODELETE, 	$arrFlags))
				$bAutoDelete	= $arrFlags[RABBIT_Connection::B_AMQP_AUTODELETE];
		}
			
		$this->_strExchangeName	= $strName;
		$this->_strExchangeType	= $strType;
		$this->_amqpChannel		= $amqpChannel;
		
		$this->_amqpChannel->exchange_declare($this->_strExchangeName, $this->_strExchangeType, $bPassive, $bDurable, $bAutoDelete);
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