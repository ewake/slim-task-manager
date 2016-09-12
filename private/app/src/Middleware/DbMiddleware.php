<?php
namespace App\Middleware;

use RedBeanPHP\R;

/*
|--------------------------------------------------------------------------
| Create Redbean DAO
|--------------------------------------------------------------------------
|
| Create the loader class R to read the connection parameters and setup
| the connection.
|
*/
class DbMiddleware extends \App\Middleware {
    
	public function run($request, $response, $next)
	{
		$settings = $this->container->get('settings');
		$logger = $this->container->get('logger');
		
		$conn = $settings['db']['connections'][$settings['db']['default']];
		
		switch($settings['db']['default']) {
			case 'mysql':
				R::setup($conn['driver'] . ':host=' . $conn['host'] . '; dbname=' . $conn['database'], $conn['username'], $conn['password']);
				break;
				
			case 'sqlite':
				R::setup($conn['driver'] . ':' . $conn['database']);
				break;
		}
		
		R::debug( $settings['db']['debug'], $settings['db']['debug_mode'] );
		
		$logger->addDebug('db', array('db' => $settings['db']['default']));
		
		$response = $next($request, $response);
		
		return $response;
	}
	
	public function log($request, $response, $next)
	{
		$settings = $this->container->get('settings');
		$logger = $this->container->get('logger');
		
		$response = $next($request, $response);
		
		if(in_array($settings['db']['debug_mode'], [1, 3])) {
			$logs = R::getDatabaseAdapter()->getDatabase()->getLogger()->getLogs();
			
			$logger->addInfo('db', array('query' => $logs));
		}
		
		return $response;
	}
}
