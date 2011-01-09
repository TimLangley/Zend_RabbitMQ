<?php
/**
 * Rabbit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://github.com/canddi/Zend_RabbitMQ/blob/master/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to hello@canddi.com so we can send you a copy immediately.
 *
 */

/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

/**
 * Represents a connection to the rabbitmq server.
 */
class Rabbit_Connection
{
    const DEFAULT_HOST     = 'localhost';
    const DEFAULT_VHOST    = '/';
    const DEFAULT_PORT     = 5763;
    const DEFAULT_USER     = 'guest';
    const DEFAULT_PASSWORD = 'guest';
    
    private $_strHost     = self::DEFAULT_HOST;
    private $_strVHost    = self::DEFAULT_VHOST;
    private $_intPort     = self::DEFAULT_PORT;
    private $_strUserName = self::DEFAULT_USER;
    private $_strPassword = self::DEFAULT_PASSWORD;
    
    /**
     * @var Rabbit_Connection
     */
    private static $_defaultConnection = null;
    
    /**
     * @var Rabbit_AMQP_Connection
     */
    private $_amqpConnection = null;
    
    /**
     * Creates a new connection.
     *
     * @param array $config The connection config. Following keys expected:  
     *                                             - host
     *                                             - vhost
     *                                             - port
     *                                             - login
     *                                             - password
     */
    public function __construct($config = null)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        
        // TODO: Use constants!!
        
        // Now lets set everything
        if (array_key_exists('host', $config)) {
            $this->_strHost = $config['host'];
        }
        
        if (array_key_exists('vhost', $config)) {
            $this->_strVHost = $config['vhost'];
        }
        
        if (array_key_exists('port', $config)) {
            $this->_intPort = $config['port'];
        }
        
        if (array_key_exists('username', $config)) {
            $this->_strUserName = $config['username'];
        }
        
        if (array_key_exists('password', $config)) {
            $this->_strPassword = $config['password'];
        }
        
    }
        
    /**
     * Connects to the AMQP instance.
     * 
     * @return bool
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return;
        }
        
        $this->_amqpConnection = new Rabbit_AMQP_Connection(
            $this->_strHost,
            $this->_intPort,
            $this->_strUserName,
            $this->_strPassword,
            $this->_strVHost
        );
        
        return true;
    }
    
    /**
     * Synonym for {@link Rabbit_Connection::disconnect}
     * 
     * @return bool
     */
    public function close()
    {
        return $this->disconnect();
    }
    
    /**
     * Disconnects from the AMQP server and closes any connections.
     * 
     * @return bool
     */
    public function disconnect()
    {
        if ($this->_amqpConnection) {
            $this->_amqpConnection->close();
        }
        
        $this->_amqpConnection = null;
        
        return true;
    }
    
    /**
     * Returns a new Rabbit_Exchange.
     * 
     * The returned exchange is either new or existing from the server.
     * 
     * @param string       $strName The Exchange Name
     * @param string       $strType The type of Exchange one of: 
     *                        {@link Rabbit_Exchange::EXCHANGE_TYPE_DIRECT}
     *                        {@link Rabbit_Exchange::EXCHANGE_TYPE_FANOUT}
     *                        {@link Rabbit_Exchange::EXCHANGE_TYPE_TOPIC}
     * @param Rabbit_Flags $flags   The flags to use with in the exchange.
     * 
     * @see Rabbit_Flags
     * 
     * @return Rabbit_Exchange
     */
    public function getExchange($strName = null, $strType = null,
        Rabbit_Flags $flags = null)
    {
    
        if (is_null($flags)) {
            $flags = new Rabbit_Flags(array());
        }
        
        $this->connect();
        
        $amqpChannel = $this->_amqpConnection->channel();
        $amqpChannel->access_request(
            $this->_strVHost,
            $flags->getExclusive(),
            $flags->getPassive(),
            $flags->getActive(),
            true
        );
        
        return new Rabbit_Exchange($amqpChannel, $strName, $strType, $flags);
    }
    
    /**
     * Returns a new Rabbit_Queue.
     * 
     * The returned exchange is either new or existing from the server.
     * 
     * @param string       $strName The Queue Name
     * @param Rabbit_Flags $flags   The flags to use with in the exchange.
     * 
     * @see Rabbit_Flags
     * 
     * @return Rabbit_Queue
     */
    public function getQueue($strName = null, Rabbit_Flags $flags = null)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        $amqpChannel = $this->_amqpConnection->channel();
        
        if (is_null($flags)) {
            $flags = new Rabbit_Flags(array());
        }
        
        $amqpChannel->access_request(
            $this->_strVHost,
            $flags->getExclusive(),
            $flags->getPassive(),
            $flags->getActive(),
            false
        );
        
        return new Rabbit_Queue($amqpChannel, $strName, $flags);
    }

    /**
     * Checks if the connection is open or not.
     * 
     * @return bool
     */
    public function isConnected()
    {
        return !is_null($this->_amqpConnection);
    }
    
    /**
     * Sets the host.
     * 
     * @param string $strHost The host to use.
     * 
     * @return void
     */
    public function setHost($strHost = null)
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }
        
        // FIXME: This should throw exceptions not go with the default value.
        $this->_strHost = is_null($strHost) ? 
            self::DEFAULT_HOST : $strHost;
    }
    
    /**
     * Sets the virtual host to use.
     * 
     * @param string $strVHost The virtual host.
     * 
     * @return void
     */
    public function setVHost($strVHost = null)
    {
        if ($this->isConnected()) {
            $this->disconnect();
        }

        // FIXME: This should throw exceptions not go with the default value.
        $this->_strVHost = is_null($strVHost) ?
            self::DEFAULT_VHOST : $strVHost;
    }
    
    /**
     * Sets the Username and Password for authentication.
     * 
     * @param string $strUserName The username to use.
     * @param string $strPassword The password to use.
     * 
     * @return void
     */
    public function setLogin($strUserName = null, $strPassword = null)
    {
        // TODO: If we close the connection should we connect it again?
        if ($this->isConnected()) {
            $this->disconnect();
        }
        
        // FIXME: This should throw exceptions not go with the default value.
        $this->_strUserName = is_null($strUserName) ?
            self::DEFAULT_USER : $strUserName;
        $this->_strPassword = is_null($strPassword) ?
            self::DEFAULT_PASSWORD : $strPassword;
    }
    
    /**
     * Sets the port.
     * 
     * @param int $port The port to use.
     * 
     * @return void
     */
    public function setPort($port)
    {
        // TODO: If we close the connection should we connect it again?
        if ($this->isConnected()) {
            $this->disconnect();
        }
        
        // TODO: Throw exception if null or not valid.
        $this->_intPort = $port;
    }
    
    /**
     * Retrieves the default connection.
     *
     * @return Rabbit_Connection
     */
    public static function getDefaultConnection()
    {
        return self::$_defaultConnection;
    }
    
    /**
     * Sets the current instance as the default connection.
     * 
     * @return void
     */
    public function setDefaultConnection()
    {
        self::$_defaultConnection = $this;
    }
    
}