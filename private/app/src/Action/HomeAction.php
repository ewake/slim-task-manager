<?php
namespace App\Action;

use Symfony\Component\Process\Process;
use RedBeanPHP\R;

final class HomeAction extends \App\Action
{
	public function dispatch($request, $response, $args)
	{ 
		$view = $this->container->get('view');
		$router = $this->container->get('router');
		$logger = $this->container->get('logger');
		$flash = $this->container->get('flash');
		$TaskHelper = $this->container->get('App\Helper\TaskHelper');
		
		if(($task = $TaskHelper->checkRunning()) !== null)
			$flash->addNowMessage('info', '<div class="media"><div class="media-left media-middle"><i class="fa fa-cog fa-spin fa-2x"></i></div><div class="media-body media-middle">'.sprintf(_('Task #%1$d in esecuzione...'), $task->task_id).'</div></div>');
		
		$args['task_log'] = $TaskHelper->getLogBuffer();
		
		$args['flashes'] = $flash->getMessages();
		
		$args['id'] = 0;
		
		d($args);
		
		$view->render($response, 'home.twig', $args);
		
		return $response;
	}
}
