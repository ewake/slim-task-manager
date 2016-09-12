<?php
// DIC configuration

$container = $app->getContainer();


// -----------------------------------------------------------------------------
// Parameters
// -----------------------------------------------------------------------------


// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------

// Twig
$container['view'] = function ($c) {
	$settings = $c->get('settings');

	$view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);
	
	$view->getEnvironment()->addGlobal('settings', $settings);
	$view->getEnvironment()->addGlobal('lang', $c->get('lang'));
	
	// Add extensions
	//$view->addExtension(new Slim\Views\TwigExtension($c->get('router'), $c->get('request')->getUri()));
	$view->addExtension(new App\TwigExtension($c));
	$view->addExtension(new Twig_Extension_Debug());

	return $view;
};

// Flash messages
$container['flash'] = function ($c) {
	//return new \Slim\Flash\Messages;
	return new \App\FlashMessages;
};

// HttpCache
$container['cache'] = function ($c) {
	new \Slim\HttpCache\CacheProvider();
};


// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------

// monolog
/*
 * DEBUG (100): Detailed debug information.
 * INFO (200): Interesting events. Examples: User logs in, SQL logs.
 * NOTICE (250): Normal but significant events.
 * WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
 * ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
 * CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
 * ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
 * EMERGENCY (600): Emergency: system is unusable.
 */
$container['logger'] = function ($c) {
	$settings = $c->get('settings');
	
	$logger = new \Monolog\Logger($settings['logger']['name']);
	
	//$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	//$logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], \Monolog\Logger::INFO));
	
	//https://devsware.wordpress.com/2014/06/14/how-to-configure-swiftmailer-with-monolog/
	$logger->pushHandler(new \Monolog\Handler\RotatingFileHandler($settings['logger']['path'], $settings['logger']['maxFiles'], \Monolog\Logger::INFO));
	$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
	$logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(\Monolog\Logger::INFO));
	
	if($settings['debug']) {
		//$logger->pushHandler(new \Monolog\Handler\FirePHPHandler());
		//$logger->pushHandler(new \Monolog\Handler\ChromePHPHandler());
		$logger->pushHandler(new \Monolog\Handler\BrowserConsoleHandler());
		//$logger->pushHandler(new \Monolog\Handler\PHPConsoleHandler());
	}
	
	$message = Swift_Message::newInstance()
		->setReturnPath($settings['mail']['return_path'])
		->setFrom(array($settings['mail']['return_path']))
		->setSubject(sprintf(_('Error reporting from %1$s'), getenv('SERVER_ADDR')))
		->setBody('', 'text/html')
		->addPart('', 'text/plain')
		;
	
	foreach($settings['mail']['debug_emails_to'] as $key => $val) {
		if (is_int($key)) {
			$message->setTo($val);
		} else {
			$message->setTo(array($key => $val));
		}
	}
	
	$mailStream = new \Monolog\Handler\SwiftMailerHandler($c->get('mailer'), $message, \Monolog\Logger::ERROR);
	$mailStream->setFormatter(new \Monolog\Formatter\HtmlFormatter());
	
	$logger->pushHandler($mailStream);
	
	return $logger;
};

// mailer
$container['mailer'] = function ($c) {
	$settings = $c->get('settings');
	
	switch($settings['mail']['transport']) {
		case 'smtp':
			$transport = Swift_SmtpTransport::newInstance($settings['mail']['smtp_host'], $settings['mail']['smtp_port'], $settings['mail']['smtp_encryption'])
			->setUsername($settings['mail']['smtp_username'])
			->setPassword($settings['mail']['smtp_password'])
			;
			break;
	
		case 'sendmail':
			$transport = Swift_SendmailTransport::newInstance($settings['mail']['sendmail_path']);
			break;
	
		default:
			$transport = Swift_MailTransport::newInstance();
			break;	
	}
	
	$mailer = Swift_Mailer::newInstance($transport);
	
	return $mailer;
};

if(!$app->settings['debug']) {
	// Override the default Error Handler
	/*$container['errorHandler'] = function ($c) {
		return function ($request, $response, $exception) use ($c) {
			return $c['response']
			->withStatus(500)
			->withHeader('Content-Type', 'text/html')
			->write(_('Something went wrong!'));
		};
	};*/
	
	// Override the default Not Found Handler
	/*$container['notFoundHandler'] = function ($c) {
		return function ($request, $response) use ($c) {
			return $c['response']
			->withStatus(404)
			->withHeader('Content-Type', 'text/html')
			->write(_('Page not found'));
		};
	};*/
	
	// Override the default Not Allowed Handler
	/*$container['notAllowedHandler'] = function ($c) {
		return function ($request, $response, $methods) use ($c) {
			return $c['response']
			->withStatus(405)
			->withHeader('Allow', implode(', ', $methods))
			->withHeader('Content-type', 'text/html')
			->write(sprintf(_('Method must be one of: %1$s'), implode(', ', $methods)));
		};
	};*/
}


// -----------------------------------------------------------------------------
// Middleware factories
// -----------------------------------------------------------------------------
$container['App\Middleware\KintMiddleware'] = function ($c) {
	return new App\Middleware\KintMiddleware($c);
};

$container['App\Middleware\LangMiddleware'] = function ($c) {
	return new App\Middleware\LangMiddleware($c);
};

$container['App\Middleware\DbMiddleware'] = function ($c) {
	return new App\Middleware\DbMiddleware($c);
};

$container['App\Middleware\MinifyMiddleware'] = function ($c) {
	return new App\Middleware\MinifyMiddleware($c);
};


// -----------------------------------------------------------------------------
// Action factories
// -----------------------------------------------------------------------------

/*$container['App\Action\HomeAction'] = function ($c) {
	return new App\Action\HomeAction($c->get('view'), $c->get('logger'));
};*/


// -----------------------------------------------------------------------------
// Action factories - created via an AbstractFactory
// -----------------------------------------------------------------------------
$container->addAbstractFactory(new App\ActionAbstractFactory());


// -----------------------------------------------------------------------------
// Helper factories - created via an AbstractFactory
// -----------------------------------------------------------------------------
$container->addAbstractFactory(new App\HelperAbstractFactory());

