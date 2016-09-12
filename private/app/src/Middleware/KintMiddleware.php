<?php
namespace App\Middleware;

class KintMiddleware extends \App\Middleware
{
	public function startTrace($request, $response, $next)
	{
		$settings = $this->container->get('settings');
		
		if($settings['debug_trace'])
			$this->container['debug_trace'] = @\Kint::trace();
		
		$response = $next($request, $response);
	
		return $response;
	}
	
	public function endTrace($request, $response, $next)
	{
		$settings = $this->container->get('settings');
	
		$response = $next($request, $response);

		if($settings['debug_trace'])
			$response->getBody()->write($this->container->get('debug_trace'));
	
		return $response;
	}
}