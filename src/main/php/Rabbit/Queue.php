<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class RABBIT_Queue															{
	private $_amqpChannel;
	private $_strQueueName;
	private $_strConsumerTag;
	
	public function __construct(RABBIT_AMQP_Channel $amqpChannel, 
								$strQueueName 					= null,
								$flags							= null)		{
	/**
	 *	@purpose: This loads a new RABBIT_Queue (or creates one)
	 *	@NOTE:		This is a bit ugly - because whilst this is a public constructor
	 *					it's not possible to create these directly because can't get access to the Channel 
	 *					(outside of the RABBIT_Connection)
	 *	@param:		$amqpChannel
	 *	@param:		$strName	- the Queue Name
	 *	@param:		$flags		- Queue creation flags
	 */
		if(is_null($strQueueName))
			throw new RABBIT_Exception_Queue(RABBIT_Exception_Queue::ERROR_QUEUE_NAME_EMPTY);
		if(is_null($amqpChannel))
			throw new RABBIT_Exception_Queue(RABBIT_Exception_Queue::ERROR_CHANNEL_EMPTY);
			
		$this->_strQueueName	= $strQueueName;	
		$this->_amqpChannel		= $amqpChannel;
		
		$this->_amqpChannel->queue_declare($this->_strQueueName);
								}
	public function ack($delivery_tag)										{
		 $this->_amqpChannel->basic_ack($delivery_tag);
	}
	public function bind($strExchangeName, $strRoutingKey 		= null)		{
		$this->_amqpChannel->queue_bind($this->_strQueueName, $strExchangeName, $strRoutingKey);
	}
	public function callbackConsume ( RABBIT_Message $msg
									, $fnUserCallback 			= null)		{
		
		$this->_amqpChannel->basic_ack($msg->delivery_info['delivery_tag']);

		// Cancel callback
		if ($msg->body === 'quit') 
			$this->_amqpChannel->basic_cancel($this->_strConsumerTag);
		else
			if(!is_null($fnUserCallback))
				call_user_func($fnUserCallback, $msg);
									}
	public function cancel($strConsumerTag)									{
		$this->_amqpChannel->basic_cancel($strConsumerTag);
	}
	public function consume($fnCallback,
							$strConsumerTag,
							$options = null)								{
		/**
		 *	@purpose:	This "sits" consuming the messages - when one arrives it calls the function $fnCallback
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
	public function delete()												{
		$this->_amqpChannel->queue_delete($this->_strQueueName);
	}
	public function get($flags = null)										{
		return $this->_amqpChannel->basic_get($this->_strQueueName);
	}
	public function purge()													{
		$this->_amqpChannel->queue_purge($this->_strQueueName);
	}
}
