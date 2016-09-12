<?php
namespace App\Action;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RedBeanPHP\R;

final class ApiAction extends \App\Action
{
	public function task_sse($request, $response, $args)
	{
		$logger = $this->container->get('logger');
		$TaskHelper = $this->container->get('App\Helper\TaskHelper');

		$buffer = '';
		
		$newResponse = $response->withHeader('Content-type', 'text/event-stream');
		$newResponse->withHeader('Cache-Control', 'no-cache');
		
		$logs = R::find( 'log', ' new = 1 ORDER BY id ASC LIMIT 1 ' );
			
		$tot_logs = count($logs);
		
		$buffer .= ':'.str_repeat(" ", 2048).PHP_EOL; // 2 kB padding for IE
				
		if($tot_logs || ($task = $TaskHelper->checkRunning()) !== null) {
			
			if( $tot_logs ) {	
				$buffer .= 'retry: 2000'.PHP_EOL;	
				
				foreach($logs as $log) {
					if(strpos($log->title, '|')) {
						list($label, $title) = explode('|', $log->title);
						$title = ' '.$title;
						$label_class = $TaskHelper->getLabel($label);
					} else {
						$label_class = $TaskHelper->getLabel($log->title);
						if($label_class) {
							$label = $log->title;
							$title = '';
						} else {
							$label = '';
							$title = $log->title;
						}
					}
					
					$buffer .= 'id: '.$log->id.PHP_EOL;
					if(in_array($label, ['fail']))
						$buffer .= 'event: myError'.PHP_EOL;
					$buffer .= 'data: {'.PHP_EOL;
					$buffer .= sprintf('data: "idate": "%1$s",', $log->idate).PHP_EOL;
					$buffer .= sprintf('data: "task_id": %1$d,', $log->task_id).PHP_EOL;
					$buffer .= sprintf('data: "label": "%1$s",', htmlspecialchars($label, ENT_QUOTES, 'UTF-8')).PHP_EOL;
					$buffer .= sprintf('data: "title": "%1$s"', htmlspecialchars($title, ENT_QUOTES, 'UTF-8')).PHP_EOL;
					$buffer .= 'data: }'.PHP_EOL;
					
					$log->new = 0;
					R::store( $log );
				}			
			}
		} else {
			$buffer .= 'event: myStop'.PHP_EOL;
			$buffer .= 'data: '.PHP_EOL;
		}
		
		$buffer .= PHP_EOL;

		return $newResponse->write($buffer);
	}
	
	public function task_reset($request, $response, $args)
	{
		$logger = $this->container->get('logger');
		$router = $this->container->get('router');
		$flash = $this->container->get('flash');
		$TaskHelper = $this->container->get('App\Helper\TaskHelper');

		if(($task = $TaskHelper->checkRunning()) !== null) {
			if($task->pid > 0) {
				$pid = intval($task->pid + 1); //FIXME
				
				$result = shell_exec(sprintf('ps -p %1$d', $pid)); 

				if(count(preg_split("/\n/", $result)) > 2) {	
					//exec('kill -9 '.$pid, $output);
					$process = new Process('kill -9 '.$pid);
					$process->run();
					
					if (!$process->isSuccessful()) {
						$TaskHelper->addLog(0, sprintf('fail|impossibile terminare pid #%1$d', $pid));
						
						$logger->addCritical('task_reset', ['fail' => sprintf('impossibile terminare pid #%1$d', $pid)]);
						
						$flash->addMessage('warning', _('Sarà possibile eseguire resettare i tasks solo al termine del processo in corso.'));
					} else {
						$TaskHelper->addLog(0, 'reset');
						
						R::exec( 'UPDATE task SET running = 0' );
						R::exec( 'UPDATE task SET done = 0' );
					}
				} else {
					$TaskHelper->addLog(0, sprintf('warning|pid #%1$d non trovato', $pid));
					
					$logger->addCritical('task_reset', ['fail' => sprintf('pid #%1$d non trovato', $pid)]);
					
					R::exec( 'UPDATE task SET running = 0' );
					R::exec( 'UPDATE task SET done = 0'  );
				}
			} else {
				$TaskHelper->addLog(0, 'fail|pid nullo');
					
				$logger->addCritical('task_reset', ['fail' =>'pid nullo']);
				
				$flash->addMessage('warning', _('Sarà possibile eseguire resettare i tasks solo al termine del processo in corso.'));
			}
		} else {
			$TaskHelper->addLog(0, 'reset');
			
			R::exec( 'UPDATE task SET done = 0' );
		}
		
		/*
		//R::exec( 'DELETE log WHERE task_id = 6' );
		$log = R::find( 'log', ' task_id = 6 ');
		R::trashAll( $log );

		R::exec( 'UPDATE task SET running = 0' );
		R::exec( 'UPDATE task SET done = 1 WHERE task_id <= 5' );
		*/
	
		return (isset($args['id']) && $args['id'] > 0) 
			? $response->withStatus(301)->withHeader('Location', $router->pathFor('task', $args)) 
			: $response->withStatus(301)->withHeader('Location', $router->pathFor('home'));
	}
	
	public function wipe_log($request, $response, $args)
	{
		$settings = $this->container->get('settings');
		$router = $this->container->get('router');
		
		R::wipe( 'log' );
		
		//$fs = new Filesystem();
		//$fs->remove(glob(dirname($settings['logger']['path']).'/*'));
		
		return (isset($args['id']) && $args['id'] > 0) 
			? $response->withStatus(301)->withHeader('Location', $router->pathFor('task', $args)) 
			: $response->withStatus(301)->withHeader('Location', $router->pathFor('home'));
	}
	
	public function db_nuke($request, $response, $args)
	{
		$router = $this->container->get('router');
		
		R::nuke();
		
		return (isset($args['id']) && $args['id'] > 0) 
			? $response->withStatus(301)->withHeader('Location', $router->pathFor('task', $args)) 
			: $response->withStatus(301)->withHeader('Location', $router->pathFor('home'));
	}
}
