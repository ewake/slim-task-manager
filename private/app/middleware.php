<?php
// -----------------------------------------------------------------------------
// Application middleware
// -----------------------------------------------------------------------------

// Kint start trace
$app->add('App\Middleware\KintMiddleware:startTrace');


// CSRF Protection
$guard = new \Slim\Csrf\Guard();
$guard->setFailureCallable(function ($request, $response, $next) {
	$request = $request->withAttribute('csrf_result', 'FAILED');	
	return $next($request, $response);
});		
$app->add($guard);


// HttpCache
$app->add(new \Slim\HttpCache\Cache('public', 86400));


// languages
$app->add('App\Middleware\LangMiddleware:run');


// http_referer
/*$app->add(function ($request, $response, $next) {
	$env = $this->get('environment');
	$logger = $this->get('logger');
	
	if(!isset($_SESSION['http_referer']))
		$_SESSION['http_referer'] = $env['HTTP_REFERER'];
	
	$this['http_referer'] = $_SESSION['http_referer'];
	
	$logger->addDebug('http_referer', array('http_referer' => $this->get('http_referer')));
	
	$response = $next($request, $response);

	return $response;
});*/


// Database start
$app->add('App\Middleware\DbMiddleware:run');


// Database log
$app->add('App\Middleware\DbMiddleware:log');


// Minify
$app->add('App\Middleware\MinifyMiddleware');


// Kint end trace
$app->add('App\Middleware\KintMiddleware:endTrace');