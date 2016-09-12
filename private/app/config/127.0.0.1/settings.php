<?php
// no 'settings' key
return [
		'displayErrorDetails' => true,
		
		'mode' => 'development',
		
		'debug' => true,
		'debug_trace' => false,
			
		'private_key' => md5(getenv('PRIVATE_KEY')),
		
		'tasks_max_execution_time' => (3600 * 6),
		
		'public_unix_owner' => 'root',
		'public_unix_group' => 'root',
			
		'cache_suffix' => mt_rand(),
		
		'public2_path' => dirname(dirname(_ROOT)) . '/httpdocs',
];