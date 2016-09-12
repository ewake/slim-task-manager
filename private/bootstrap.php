<?php
if (!defined('_PUBLIC'))
	define('_PUBLIC', _BOOT);

if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	// To help the built-in PHP dev server, check if the request was actually for
	// something which should probably be served as a static file
	$file = _BOOT . getenv('REQUEST_URI');
	if (is_file($file)) {
		return false;
	}
	
	putenv("SERVER_ADDR=".gethostbyname(gethostname()));
}

require _ROOT . '/vendor/autoload.php';

session_start();

// DOTENV
$dotenv = new Dotenv\Dotenv(_ROOT);
$dotenv->load();

$dotenv->required([
		'PRIVATE_KEY', 
		'DB_USER', 
		'DB_PASS', 
		'DB_NAME', 
		'DB2_USER',
		'DB2_PASS',
		'DB2_NAME',
]);

$files = glob( _ROOT . '/app/config/{*,'.getenv('SERVER_ADDR').'/*}.{php,json,ini,xml,yaml,properties}', GLOB_BRACE );
$settings = Zend\Config\Factory::fromFiles($files);

$container = new RKA\ZsmSlimContainer\Container(['settings' => $settings]);

$app = new \Slim\App($container);

Kint::enabled($app->settings['debug']);

require _ROOT . '/app/env.php';
require _ROOT . '/app/dependencies.php';
require _ROOT . '/app/middleware.php';
require _ROOT . '/app/routes.php';

return $app;
