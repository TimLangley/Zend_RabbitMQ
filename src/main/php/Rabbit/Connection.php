<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

class RABBIT_Connection														{
	const	DEFAULT_HOST				= "localhost";
	const	DEFAULT_VHOST				= "/";
	const	DEFAULT_PORT				= 5763;
	const	DEFAULT_USER				= "guest";
	const	DEFAULT_PASSWORD			= "guest";
	
	private $_strHost					= self::DEFAULT_HOST;
	private $_strVHost					= self::DEFAULT_VHOST;
	private $_intPort					= self::DEFAULT_PORT;
	private $_strUserName				= self::DEFAULT_USER;
	private $_strPassword				= self::DEFAULT_PASSWORD;
	
	private static $_defaultConnection	= null;	//This holds a version of RABBIT_Connection
	
	private $_amqpConnection			= null;
	
	public function __construct($config = null)								{
		/**
		 *	@purpose: 	Created a new RABBIT_Connection
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
		 *	@return:	true | false
		 */
		$this->_amqpConnection			= new RABBIT_AMQP_Connection($this->_strHost
																	,$this->_intPort
																	,$this->_strUserName
																	,$this->_strPassword
																	,$this->_strVHost
																	);
	}
	public function close()													{
		$this->disconnect();
	}
	public function disconnect()											{
		/**
		 *	@purpose:	
		 */
		if($this->_amqpConnection)
			$this->_amqpConnection->close();
		$this->_amqpConnection			= null;
	}
	public function getExchange(	$strName		= null
								, 	$strType 		= null
								, 	$flags 			= null)					{
		/**
		 *	@purpose:	Returns a new RABBIT_Exchange (either creating or finding existing from the server)
		 */
		if(!$this->isConnected())
			$this->connect();
		$amqpChannel 	= $this->_amqpConnection->channel();
		$amqpChannel->access_request($this->_strVHost, false, false, true, true);
		return new RABBIT_Exchange($amqpChannel, $strName, $strType, $flags);
								}
	public function getQueue(		$strName		= null)					{
		/**
		 *	@purpose:	Returns a new RABBIT_Queue (either creating or finding existing from the server)
		 */
		if(!$this->isConnected())
			$this->connect();
		$amqpChannel 	= $this->_amqpConnection->channel();
		$amqpChannel->access_request($this->_strVHost, false, false, true, true);
		return new RABBIT_Queue($amqpChannel, $strName);
	}
	public function isConnected()											{
		/**
		 *	@purpose:	Returns true | false whether we're currently connected
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