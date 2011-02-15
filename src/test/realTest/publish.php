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
defined('APPLICATION_ENV')		or define('APPLICATION_ENV', 	ENVIRONMENT_UNIT_TEST);
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

$EXCHANGE 		  = 'WebAnalytics-fan1';
$msg_body       = isset($argv[1])?$argv[1]:"quit";
$ROUTING		    = isset($argv[2])?$argv[2]:null;

$rabbitConn     = new Rabbit_Connection($arrOptions);
$rabbitExchange = $rabbitConn->getExchange($EXCHANGE, Rabbit_Exchange::EXCHANGE_TYPE_FANOUT);

$msg            = new Rabbit_Message($msg_body, array('content_type' => 'text/plain'));
$rabbitExchange->publish($msg, $ROUTING);
$rabbitConn->close();
