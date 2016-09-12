#!/usr/bin/env php
<?php
define('_ROOT', dirname(__DIR__));
define('_BOOT', __DIR__);
define('_PUBLIC', dirname(dirname(__DIR__)).'/domains/dev.domain.tld/web');

$app = require_once _ROOT . '/bootstrap.php';

$app->run(true);

use RedBeanPHP\R;

try {
	ob_implicit_flush(true);
	sleep(3);
	
	if(!isset($argv[1]))
		throw new Exception(_('1 argument required.'));
	
	if(!file_exists(_BOOT.'/tasks/'.$argv[1].'.php'))
		throw new Exception(_('Task file doesn\'t exists.'));

	try {
		$TaskHelper = $container->get('App\Helper\TaskHelper');
		
		$task  = R::findOne( 'task', ' task_id = :task_id ', ['task_id' => $argv[1]] );
		if(!isset($task->id)) {
			$task = R::dispense( 'task' );
				
			$task->task_id = $argv[1];
			$task->running = 1;
			$task->done = 0;
			$bean_id = R::store( $task );
			
			$TaskHelper->addLog($task->task_id, sprintf('warning|task_id #%1$d ricreato', $task->task_id));
			
			$app->logger->addWarning('task_id_not_found', ['task_id' => $task->task_id]);
		}	
		
		include _BOOT.'/tasks/'.$argv[1].'.php';
		
		$TaskHelper->addLog($task->task_id, sprintf('complete|pid #%1$d', $task->pid));
		
		$task->running = 0;
		$task->done = 1;
		$bean_id = R::store( $task );	
		
	} catch(Exception $e) {
		$TaskHelper->addLog($task->task_id, sprintf('fail|pid #%1$d', $task->pid));
		
		$app->logger->addCritical('exception', ['getMessage' => $e->getMessage()]);
		
		$task->running = 0;	
		$task->done = 0;
		$bean_id = R::store( $task );
	}
} catch(Exception $e) {
	$app->logger->addCritical('exception', ['getMessage' => $e->getMessage()]);
}