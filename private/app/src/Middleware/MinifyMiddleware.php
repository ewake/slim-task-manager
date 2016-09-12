<?php
namespace App\Middleware;

class MinifyMiddleware extends \App\Middleware
{
	public function __invoke($request, $response, $next)
	{
		$settings = $this->container->get('settings');
		
		$response = $next($request, $response);
		
		if($settings['minify']['status']) {

			$output = $response->getBody()->__toString();
		
			//TODO
			/*if($settings['minify']['cache_type']) {
				switch( $settings['minify']['cache_type'] ) {
					case 'file':
						\Minify::setCache( $settings['minify']['cache_path'] );
						break;
				
					case 'apc':
						\Minify::setCache( new \Minify_Cache_APC( ) );
						break;
				
					case 'xcache':
						\Minify::setCache( new \Minify_Cache_XCache() );
						break;
				
					//https://github.com/juliangut/slim-doctrine-middleware
					case 'memcache':	
						\Minify::setCache( new \Minify_Cache_Memcache(  ) );
						break;
				}
				
			} else {*/	
						
				//call_user_func() expects parameter 1 to be a valid callback, array must have exactly two members in .../vendor/mrclay/minify/lib/Minify/HTML.php on line 207
				$output = @\Minify_HTML::minify( $output, $settings['minify']['options']);
							
			/*}*/
			
			$body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
			
			$body->write($output);
			
			$response = $response->withBody($body);
		}
		
		return $response;
	}
}