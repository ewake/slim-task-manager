<?php
namespace App\Action;

use Symfony\Component\Process\Process;

final class PageAction
{
	protected $container;
	
	public function __construct($container)
	{
		$this->container = $container;
	}
	
	public function dispatch($request, $response, $args)
	{ 
		$view = $this->container->get('view');
		$router = $this->container->get('router');
		$logger = $this->container->get('logger');
		$flash = $this->container->get('flash');
	    
		$args['flash'] = $flash->getMessages();
		
		// check task

		
		$view->render($response, $args['page'].'.twig', $args);
		
		return $response;
	}
}
