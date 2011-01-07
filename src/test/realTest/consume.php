#!/usr/bin/php
<?
/**
 * @category   
 * @package    
 * @copyright  2011-01-01, Campaign and Digital Intelligence Ltd
 * @license    
 * @author     Tim Langley
 */

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
                   ,  "vhost"   => "/"
                   ,  "port"    => 5672
                   ,  "username"=> "guest"
                   ,  "password"=> "guest");

$EXCHANGE 		  = 'newExchange-fan';
$QUEUE 			    = isset($argv[1])?$argv[1]:'msgs';
$CONSUMER_TAG 	= $QUEUE;

$rabbitConn     = new Rabbit_Connection($arrOptions);
$rabbitQueue    = $rabbitConn->getQueue($QUEUE);
$rabbitExchange = $rabbitConn->getExchange($EXCHANGE, Rabbit_Exchange::EXCHANGE_TYPE_FANOUT);
$rabbitQueue->bind($EXCHANGE);

$rabbitQueue->consume(function ($msg) {
    echo "\n--------\n";
    echo $msg->body;
    echo "\n--------\n";
}, $CONSUMER_TAG);

$rabbitConn->close();
