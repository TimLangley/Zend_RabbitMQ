<?
/**
 *	This is used in the Testing and Validation phase of the mvn test
 */	

define('RABBIT_PATH', 		realpath(dirname(__FILE__)).'/src/main/php/Rabbit');
define('RABBIT_TEST_PATH',	realpath(dirname(__FILE__)).'/src/test/php/Rabbit/');
$paths = array(	get_include_path(),
				RABBIT_PATH,
				realpath(dirname(__FILE__)).'/src/main/php',
				realpath(dirname(__FILE__)).'/target/phpinc',
				'.');
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'environments.php';
defined('APPLICATION_ENV')		or define('APPLICATION_ENV', 	ENVIRONMENT_UNIT_TEST);
defined('APPLICATION_PATH') 	or define('APPLICATION_PATH', 	realpath(dirname(__FILE__)).'/src/main/php/Rabbit');
defined('MOCK_DB_PATH')			or define('MOCK_DB_PATH', 		realpath(dirname(__FILE__)).'/src/test/testDB/');

require_once "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('PHPUnit_');
$autoloader->registerNamespace('Zend_');
$autoloader->registerNamespace('Rabbit_');

PHPUnit_Util_Filter::addDirectoryToWhitelist(RABBIT_PATH);

