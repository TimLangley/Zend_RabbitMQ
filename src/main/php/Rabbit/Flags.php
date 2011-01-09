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
 * @author Franco Zeoli
 */

/**
 * Model object to simplify the flags passing.
 */
class Rabbit_Flags
{

    /**
     * Protocol flag. No idea what it does!
     * 
     * @var string
     */ 
    const B_AMQP_ACTIVE = 'CONN_Active';
    
    /**
     * Protocol flag. For exchanges, the auto delete flag indicates that the
     * exchange will be deleted as soon as no more queues are bound to it.
     * If no queues were ever bound the exchange, the exchange will never be
     * deleted.
     * 
     * @var string
     */
    const B_AMQP_AUTODELETE = 'CONN_AutoDelete';
    
    /**
     * Protocol flag. Durable exchanges and queues will survive a broke
     * restart, complete with all of their data.
     * 
     * @var string
     */
    const B_AMQP_DURABLE = 'CONN_Durable';

    // @FIXME: not valid for exchanges?
    /**
     * Protocol flag. Only ONE client can connect to this queue.
     * 
     * @var string
     */
    const B_AMQP_EXCLUSIVE = 'CONN_Exclusive';

    /**
     * Protocol flag. Checks if Exchange exists. Passive exchanges are queues
     * will not be redeclared, the broker will throw an error if the exchange
     * does not exist.
     *  
     * @var string
     */
    const B_AMQP_PASSIVE = 'CONN_Passive';
 
    private $_passive = false;
    private $_durable = true;
    private $_autodelete = false;
    private $_exclusive = false;
    private $_active = true;
    
    /**
     * Creates a new flag object.
     * 
     * @param array|Zend_Config $flags The flags to use.
     */
    public function __construct($flags = array())
    {
        if ($flags instanceof Zend_Config) {
            $flags = $flags->toArray();
        }
        
        if (array_key_exists(self::B_AMQP_PASSIVE, $flags)) {
            $this->_passive = $flags[self::B_AMQP_PASSIVE];
        }
        
        if (array_key_exists(self::B_AMQP_DURABLE, $flags)) {
            $this->_durable = $flags[self::B_AMQP_DURABLE];
        }
        
        if (array_key_exists(self::B_AMQP_AUTODELETE, $flags)) {
            $this->_autodelete = $flags[self::B_AMQP_AUTODELETE];
        }
        
        if (array_key_exists(self::B_AMQP_EXCLUSIVE, $flags)) {
            $this->_exclusive = $flags[self::B_AMQP_EXCLUSIVE];
        }
        
        if (array_key_exists(self::B_AMQP_ACTIVE, $flags)) {
            $this->_active = $flags[self::B_AMQP_ACTIVE];
        }
    }
    
    /**
     * Checks whether the passive flag is set, or not.
     * 
     * @return bool
     */
    public function getPassive()
    {
        return $this->_passive;
    }
    
    /**
     * Checks whether the durable flag is set, or not.
     * 
     * @return bool
     */
    public function getDurable()
    {
        return $this->_durable;
    }
   
    /**
     * Checks whether the autodelete flag is set, or not.
     * 
     * @return bool
     */
    public function getAutodelete()
    {
        return $this->_autodelete;
    }
    
    /**
     * Checks whether the exclusive flag is set, or not.
     * 
     * @return bool
     */
    public function getExclusive()
    {
        return $this->_exclusive;
    }
    
    /**
     * Checks whether the active flag is set, or not.
     * 
     * @return bool
     */
    public function getActive()
    {
        return $this->_active;
    }
    
}