<?php
namespace App\Action;

use Symfony\Component\Process\Process;
use RedBeanPHP\R;

final class TaskAction extends \App\Action
{
	public function dispatch($request, $response, $args)
	{ 
		$settings = $this->container->get('settings');
		$view = $this->container->get('view');
		$router = $this->container->get('router');
		$logger = $this->container->get('logger');
		$flash = $this->container->get('flash');
		$TaskHelper = $this->container->get('App\Helper\TaskHelper');
		
		$tpl = 'task'.$args['id'];
	    
		$args['csrf_name'] = $request->getAttribute('csrf_name');
		$args['csrf_value'] = $request->getAttribute('csrf_value');

		if($request->isPost()) {
			if(false !== $request->getAttribute('csrf_result')) {			
				if($TaskHelper->checkStart($args['id'])) {
					$task  = R::findOne( 'task', ' task_id = :task_id ', ['task_id' => $args['id']] );
					if(!isset($task->id)) $task = R::dispense( 'task' );
					
					$task->task_id = $args['id'];
					$task->running = 1;
					$task->done = 0;
						
					$bean_id = R::store( $task );
					
					$process = new Process('/usr/bin/php '._ROOT.'/cli/task.php '.$args['id'].' &');
					//$process->disableOutput();
					$process->setTimeout($settings['tasks_max_execution_time']);
					$process->start();
	
					$task->pid = $process->getPid();
					$bean_id = R::store( $task );
					
					$TaskHelper->addLog($args['id'], sprintf('start|pid #%1$d', $task->pid));
	
				} else {
					$flash->addMessage('warning', _('Non è possibile eseguire il task selezionato.'));
				}
				
				return $response->withStatus(301)->withHeader('Location', $router->pathFor('task', $args));
				
			} else {
				$logger->addInfo(_('Wrong csrf_result'), [$args, $request->getParams()]);
				
				d($request->getParams());
				
				$tpl = 'error';
			}
		}
		
		if(($task = $TaskHelper->checkRunning()) !== null)
			$flash->addNowMessage('info', '<div class="media"><div class="media-left media-middle"><i class="fa fa-cog fa-spin fa-2x"></i></div><div class="media-body media-middle">'.sprintf(_('Task #%1$d in esecuzione...'), $task->task_id).'</div></div>');
		
		$args['check_start'] = ($TaskHelper->checkStart($args['id'])) ? true : false;	
		
		$args['task_log'] = $TaskHelper->getLogBuffer();
		
		$args['flashes'] = $flash->getMessages();
		
		d($args);
		
		$view->render($response, $tpl.'.twig', $args);
		
		return $response;
	}
}
