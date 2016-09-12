<?php
// no 'settings' key
return [
		//'httpVersion' => '1.1',
    //'responseChunkSize' => 4096,
    //'outputBuffering' => 'append',
    //'determineRouteBeforeAppMiddleware' => false,
    'displayErrorDetails' => true,
		
		'mode' => 'production',
		
		'debug' => false,
		'debug_trace' => false,
		
		'app_name' => 'Tasks Manager',
		'app_sub_name' => 'domain.tld',
		'app_version' => '1.0.0',
		
		'timezone' => 'Europe/Rome',
		
		'charset' => 'UTF-8',
		
		'private_key' => md5(getenv('PRIVATE_KEY')),
		
		'log_path' => _ROOT . '/tmp/log',
		
		'base_url' => 'http://www.domain.tld',
		'dev_url' => 'http://dev.domain.tld',
		//'cdn_url' => 'http://',

		'public2_path' => dirname(dirname(_ROOT)) . '/web',

		'tot_tasks' => 8,
		'tasks_max_execution_time' => (3600 * 4),
		
		'public_unix_owner' => 'user', // 5034
		'public_unix_group' => 'group', // 5033
		
		'analytics' => 'UA-12345678-1',
		
		'cache_suffix' => 1,
		
		'credits_name' => 'EWake',
		'credits_title' => 'EWake - siti web, e-commerce, gestionali, web marketing, registrazioni domini e piani hosting a verona, vicenza e padova',
		'credits_url' => 'https://ewake.it',
];
