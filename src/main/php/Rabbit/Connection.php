<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class Rabbit_Connection														{
	const	DEFAULT_HOST				= "localhost";
	const	DEFAULT_VHOST				= "/";
	const	DEFAULT_PORT				= 5763;
	const	DEFAULT_USER				= "guest";
	const	DEFAULT_PASSWORD			= "guest";
	
	const	B_AMQP_ACTIVE				= "CONN_Active";
	const	B_AMQP_AUTODELETE			= "CONN_AutoDelete";
	const	B_AMQP_DURABLE				= "CONN_Durable";
	const	B_AMQP_EXCLUSIVE			= "CONN_Exclusive";
	const	B_AMQP_PASSIVE				= "CONN_Passive";
	
	
	private $_strHost					= self::DEFAULT_HOST;
	private $_strVHost					= self::DEFAULT_VHOST;
	private $_intPort					= self::DEFAULT_PORT;
	private $_strUserName				= self::DEFAULT_USER;
	private $_strPassword				= self::DEFAULT_PASSWORD;
	
	private static $_defaultConnection	= null;	//This holds a version of Rabbit_Connection
	
	private $_amqpConnection			= null;
	
	public function __construct($config = null)								{
		/**
		 *	@purpose: 	Created a new Rabbit_Connection
		 *	@param:		$config	= associative array 
		 *					array 	"host"		=> 'example.host',
		 *  						'vhost' 	=> '/',
		 *  						'port' 		=> 5763,
		 *   						'login' 	=> 'guest',
		 *   						'password'	=> 'guest'
		 *				$config	= Zend_Config object
		 *				$config	= null => defaults will be used
		 */
		if(is_null($config))
			return;
		if (is_a($config, "Zend_Config"))
            $config 			= $config->toArray();
		
		//Now lets set everything
		$this->_strHost			= (array_key_exists("host", $config))		?$config["host"]	:self::DEFAULT_HOST;
		$this->_strVHost		= (array_key_exists("vhost", $config))		?$config["vhost"]	:self::DEFAULT_VHOST;
		$this->_intPort			= (array_key_exists("port", $config))		?$config["port"]	:self::DEFAULT_PORT;
		$this->_strUserName		= (array_key_exists("username", $config))	?$config["username"]:self::DEFAULT_USER;
		$this->_strPassword		= (array_key_exists("password", $config))	?$config["password"]:self::DEFAULT_PASSWORD;
	}
		
	public function connect()												{
		/**
		 *	@purpose:	Connects to the AMQP instance
		 *	@return:	true || exception
		 */
		$this->_amqpConnection			= new Rabbit_AMQP_Connection($this->_strHost
																	,$this->_intPort
																	,$this->_strUserName
																	,$this->_strPassword
																	,$this->_strVHost
																	);
		return true;
	}
	public function close()													{
		/**
		 *	@purpose:	Synonym for $this->disconnect();
		 */
		return $this->disconnect();
	}
	public function disconnect()											{
		/**
		 *	@purpose:	Disconnects from the AMQP server and closes any connections
		 *	@return:	true
		 */
		if($this->_amqpConnection)
			$this->_amqpConnection->close();
		$this->_amqpConnection			= null;
		return true;
	}
	public function getExchange(	$strName		= null
								, 	$strType 		= null
								, 	$arrFlags 		= null)					{
		/**
		 *	@purpose:	Returns a new Rabbit_Exchange (either creating or finding existing from the server)
		 *	@param:		strName		The Exchange Name
		 *	@param:		strType		The type of Exchange (direct | fanout | topic 
		 *									- taken from Rabbit_Exchange::EXCHANGE_TYPE)
		 *	@param:		arrFlags	Associative array of flags
		 *								
		 *								"B_AMQP_PASSIVE"	=> Check if Exchange exists
		 *														Passive exchanges are queues will not be redeclared,
		 *														the broker will throw an error if the exchange does not exist.
		 *								"B_AMQP_DURABLE"	=> Durable exchanges and queues will survive a broker restart,
		 *														complete with all of their data.
		 *								"B_AMQP_AUTODELETE"	=> For exchanges, the auto delete flag indicates that the exchange will 
		 *														be deleted as soon as no more queues are bound to it. 
		 *														If no queues were ever bound the exchange, 
		 *														the exchange will never be deleted
		 *								"B_AMQP_EXCLUSIVE"	=>	Only ONE client can connect to this queue (? not valid for exchanges?)
		 *								"B_AMQP_ACTIVE"		=> 	?? (no idea!)
		 */
	
		$bPassive				= false;
		$bExclusive				= false;
		$bActive				= true;
		if(is_array($arrFlags))												{
			if(array_key_exists(RABBIT_Connection::B_AMQP_PASSIVE, 		$arrFlags))
				$bPassive		= $arrFlags[RABBIT_Connection::B_AMQP_PASSIVE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_EXCLUSIVE, 	$arrFlags))
				$bExclusive		= $arrFlags[RABBIT_Connection::B_AMQP_EXCLUSIVE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_ACTIVE, 		$arrFlags))
				$bActive		= $arrFlags[RABBIT_Connection::B_AMQP_ACTIVE];
		}
		
		if(!$this->isConnected())
			$this->connect();
		$amqpChannel 	= $this->_amqpConnection->channel();
		$amqpChannel->access_request($this->_strVHost, $bExclusive, $bPassive, $bActive, true);
		return new Rabbit_Exchange($amqpChannel, $strName, $strType, $arrFlags);
								}
	public function getQueue(		$strName		= null
							,		$arrFlags		= null)					{
		/**
		 *	@purpose:	Returns a new Rabbit_Queue (either creating or finding existing from the server)
		 *	@param:		$strName		The Queue Name
		 * 	@param:		$arrFlags		Associative Array of flags
		 *								
		 *								"B_AMQP_PASSIVE"	=> Check if Queue exists
		 *														Passive queues will not be redeclared,
		 *														the broker will throw an error if the queue does not exist
		 *								"B_AMQP_EXCLUSIVE"	=>	Only ONE client can connect to this queue (? not valid for exchanges?)
		 *								"B_AMQP_ACTIVE"		=> 	?? (no idea!)
		 */
		if(!$this->isConnected())
			$this->connect();
		$amqpChannel 	= $this->_amqpConnection->channel();
		
		$bPassive				= false;
		$bExclusive				= false;
		$bActive				= true;
		if(is_array($arrFlags))												{
			if(array_key_exists(RABBIT_Connection::B_AMQP_PASSIVE, 		$arrFlags))
				$bPassive		= $arrFlags[RABBIT_Connection::B_AMQP_PASSIVE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_EXCLUSIVE, 	$arrFlags))
				$bExclusive		= $arrFlags[RABBIT_Connection::B_AMQP_EXCLUSIVE];
			if(array_key_exists(RABBIT_Connection::B_AMQP_ACTIVE, 		$arrFlags))
				$bActive		= $arrFlags[RABBIT_Connection::B_AMQP_ACTIVE];
		}
		
		$amqpChannel->access_request($this->_strVHost, $bExclusive, $bPassive, $bActive, false);
		return new Rabbit_Queue($amqpChannel, $strName, $arrFlags);
							}
	public function isConnected()											{
		/**
		 *	@purpose:	Returns true | false whether we're currently connected
		 *	@return:	true | false
		 */
		return (!is_null($this->_amqpConnection));
	}
	
	public function setHost($strHost = null)								{
		/**
		 *	@purpose:	Sets the host
		 *	@param:		$strHost
		 *				If null then sets the values to their default
		 */
		if($this->isConnected())
			$this->disconnect();
		$this->_strHost		= is_null($strHost)?self::DEFAULT_HOST:$strHost;
	}
	public function setVHost($strVHost = null)								{
		/**
		 *	@purpose:	Sets the virtual host
		 *	@param:		$strVHost
		 *				If null then sets the values to their default
		 */
		if($this->isConnected())
			$this->disconnect();
		$this->_strVHost		= is_null($strVHost)?self::DEFAULT_VHOST:$strVHost;
	}
	public function setLogin($strUserName = null, $strPassword = null)		{
		/**
		 *	@purpose:	Sets the Username and Password for authentication
		 *	@param:		$strUserName
		 *	@param:		$strPassword
		 *				If null then sets the values to their default
		 */
		if($this->isConnected())
			$this->disconnect();
		$this->_strUserName	= is_null($strUserName)?self::DEFAULT_USER		:$strUserName;
		$this->_strPassword	= is_null($strPassword)?self::DEFAULT_PASSWORD	:$strPassword;
	}
	public function setPort($intPort = null)								{
		/**
		 *	@purpose:	Sets the port
		 *	@param:		$intPort
		 *				If null then sets the values to their default
		 */
		if($this->isConnected())
			$this->disconnect();
		$this->_intPort		= is_null($intPort)?self::DEFAULT_PORT:$intPort;
	}
	
	public static function 	getDefaultConnection()							{
		return $this->_defaultConnection;
	}
	public function 		setDefaultConnection()							{
		return self::$_defaultConnection = $this;
	}
}