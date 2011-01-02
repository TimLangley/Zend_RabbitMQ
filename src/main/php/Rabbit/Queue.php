<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class RABBIT_Queue																{
	private $_amqpChannel;
	private $_strQueueName;
	private $_strConsumerTag;
	
	const	MESSAGE_CONSUME_CANCEL	= 'quit';
	
	public function __construct(RABBIT_AMQP_Channel $amqpChannel, 
								$strQueueName 					= null,
								$arrFlags						= null)			{
	/**
	 *	@purpose: This loads a new RABBIT_Queue (or creates one)
	 *	@NOTE:		This is a bit ugly - because whilst this is a public constructor
	 *					it's not possible to create these directly because can't get access to the Channel 
	 *					(outside of the RABBIT_Connection)
	 *	@param:		$amqpChannel
	 *	@param:		$strName	- the Queue Name
	 *	@param:		$arrFlags	- Associative array of flags
	 *								"B_AMQP_AUTODELETE"	=> For exchanges, the auto delete flag indicates that the exchange will 
	 *														be deleted as soon as no more queues are bound to it. 
	 *														If no queues were ever bound the exchange, 
	 *														the exchange will never be deleted
	 *								"B_AMQP_DURABLE"	=> Durable exchanges and queues will survive a broker restart,
	 *														complete with all of their data.
	 *								"B_AMQP_EXCLUSIVE"	=>	Only ONE client can connect to this queue (? not valid for exchanges?)
	 *								"B_AMQP_PASSIVE"	=> Check if Exchange exists
	 *														Passive exchanges are queues will not be redeclared,
	 *														the broker will throw an error if the exchange does not exist.
	 */
		if(is_null($strQueueName))
			throw new RABBIT_Exception_Queue(RABBIT_Exception_Queue::ERROR_QUEUE_NAME_EMPTY);
		if(is_null($amqpChannel))
			throw new RABBIT_Exception_Queue(RABBIT_Exception_Queue::ERROR_CHANNEL_EMPTY);
			
		$this->_strQueueName	= $strQueueName;	
		$this->_amqpChannel		= $amqpChannel;
		
		$bPassive				= false;
		$bDurable				= true;
		$bAutoDelete			= false;
		$bExclusive				= false;
		if(!is_array($arrFlags))												{
			if(array_key_exists(RABBIT_Connection::B_AMQP_PASSIVE, 		$arrFlags))
				$bPassive		= $arrFlags[RABBIT_Connection::B_AMQP_PASSIVE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_DURABLE, 		$arrFlags))
				$bDurable		= $arrFlags[RABBIT_Connection::B_AMQP_DURABLE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_AUTODELETE, 	$arrFlags))
				$bAutoDelete	= $arrFlags[RABBIT_Connection::B_AMQP_AUTODELETE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_EXCLUSIVE, 	$arrFlags))
				$bExclusive		= $arrFlags[RABBIT_Connection::B_AMQP_EXCLUSIVE];
		}
		$this->_amqpChannel->queue_declare($this->_strQueueName, $bPassive, $bDurable, $bExclusive, $bAutoDelete);
								}
	private function ack(RABBIT_Message $msg)									{
		$strDeliveryTag			= $msg->delivery_info['delivery_tag'];
		$this->_amqpChannel->basic_ack($strDeliveryTag);
	}
	public function bind($strExchangeName, $strRoutingKey 		= null)			{
		$this->_amqpChannel->queue_bind($this->_strQueueName, $strExchangeName, $strRoutingKey);
	}
	public function consume ( $fnCallback
							, $strConsumerTag
							, $arrOptions 		= null)							{
		/**
		 *	@purpose:	This "sits" consuming the messages - when one arrives it calls the function $fnCallback
		 *	@param:		fnCallback 	- a public function 
		 */
		$this->_strConsumerTag				= $strConsumerTag;
		$this->_amqpChannel->basic_consume	( $this->_strQueueName
											, $strConsumerTag
											, false
											, false
											, false
											, false
											, $fnCallback
											, $this);

		// Loop as long as the channel has callbacks registered
		while(count($this->_amqpChannel->callbacks))
		    $this->_amqpChannel->wait();
							}
	public final function _consume_cb( RABBIT_Message $msg
									, $fnUserCallback 			= null)			{
		/**
		 *	@purpose:	This is the "generic callback" which is called by the Channel
		 *				This handles the message acknowledge 
		 */
		$this->_amqpChannel->basic_ack($msg);

		// Cancel callback
		if (self::MESSAGE_CONSUME_CANCEL	=== $msg->body) 
			return $this->consume_cancel();
		
		if(!is_null($fnUserCallback))
			call_user_func($fnUserCallback, $msg);
									}
	public function consume_cancel()											{
		/**
		 *	@purpose: 	Cancels a consume call
		 */
		$this->_amqpChannel->basic_cancel($this->_strConsumerTag);
	}
	public function delete()													{
		/**
		 *	@purpose:	Deletes the current queue	
		 */
		$this->_amqpChannel->queue_delete($this->_strQueueName);
	}
	public function get($arrFlags = null)										{
		return $this->_amqpChannel->basic_get($this->_strQueueName);
	}
	public function purge()														{
		/**
		 *	@purpose:	Clears the queue of any outstanding messages
		 */	
		$this->_amqpChannel->queue_purge($this->_strQueueName);
	}
}
