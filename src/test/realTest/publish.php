#!/usr/bin/php
<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

define('RABBIT_PATH', 		realpath(dirname(__FILE__)).'/../../main/php/Rabbit');

$paths = array(	get_include_path(),
				RABBIT_PATH,
				realpath(dirname(__FILE__)).'/../../main/php',
				realpath(dirname(__FILE__)).'/../../../target/phpinc',
				'.');
set_include_path(implode(PATH_SEPARATOR, $paths));

defined('APPLICATION_ENV')		or define('APPLICATION_ENV', 	ENVIRONMENT_UNIT_TEST);
defined('APPLICATION_PATH') 	or define('APPLICATION_PATH', 	realpath(dirname(__FILE__)).'/../../main/php/Rabbit');

require_once "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('PHPUnit_');
$autoloader->registerNamespace('Zend_');
$autoloader->registerNamespace('RABBIT_');

$arrOptions = array(  "host"    => "localhost"
                   ,  "vhost"   => "/"
                   ,  "port"    => 5672
                   ,  "username"=> "guest"
                   ,  "password"=> "guest");

$EXCHANGE 		  = 'newExchange-fan';
$QUEUE 			    = 'msgs';
$CONSUMER_TAG 	= 'consumer';

$rabbitConn     = new RABBIT_Connection($arrOptions);
$rabbitExchange = $rabbitConn->getExchange($EXCHANGE, RABBIT_Exchange::EXCHANGE_TYPE_FANOUT);

$msg_body       = implode(' ', array_slice($argv, 1));
$msg            = new RABBIT_Message($msg_body, array('content_type' => 'text/plain'));
$rabbitExchange->publish($msg);
$rabbitConn->close();
