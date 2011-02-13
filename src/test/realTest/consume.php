#!/usr/bin/php
<?php
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
**/

define('Rabbit_PATH', 		realpath(dirname(__FILE__)).'/../../main/php/Rabbit');

$paths = array(	get_include_path(),
				Rabbit_PATH,
				realpath(dirname(__FILE__)).'/../../main/php',
				realpath(dirname(__FILE__)).'/../../../target/phpinc',
				'.');
set_include_path(implode(PATH_SEPARATOR, $paths));
define('ENVIRONMENT_UNIT_TEST', 'unit-test');
defined('APPLICATION_ENV')		or define('APPLICATION_ENV', ENVIRONMENT_UNIT_TEST);
defined('APPLICATION_PATH') 	or define('APPLICATION_PATH', 	realpath(dirname(__FILE__)).'/../../main/php/Rabbit');

require_once "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('PHPUnit_');
$autoloader->registerNamespace('Zend_');
$autoloader->registerNamespace('Rabbit_');

$arrOptions = array(  "host"    => "localhost"
                   ,  "vhost"   => "/CANDDi"
                   ,  "port"    => 5672
                   ,  "username"=> "CANDDi"
                   ,  "password"=> "abc123");

$EXCHANGE 		  = 'WEB-page';
$QUEUE 			    = isset($argv[1])?$argv[1]:'test-debug';
$ROUTING		    = isset($argv[2])?$argv[2]:null;

class Tim {
  private $rabbitQueue;
  
  public function __construct($arrOptions, $QUEUE, $EXCHANGE, $ROUTING) {
    $rabbitConn     = new Rabbit_Connection($arrOptions);
    $this->rabbitQueue    = $rabbitConn->getQueue($QUEUE);
    $this->rabbitQueue->bind($EXCHANGE, $ROUTING);
  }
  
  public function listen()      {
    $callback = function($msg)  {
      echo "\n--------\n";
      var_dump($msg->body);
      echo "\n--------\n";
    };
    $this->rabbitQueue->consume($callback,   "NOT TIM");
  }
}

$objTim = new Tim($arrOptions, $QUEUE, $EXCHANGE, $ROUTING);
$objTim->listen();