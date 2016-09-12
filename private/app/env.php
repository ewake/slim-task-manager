<?php
if($app->settings['debug']) {
	# PHP error handling for development servers

	# disable display of startup errors
	ini_set('display_startup_errors', 'on');

	# disable display of all other errors
	ini_set('display_errors', 'on');

	# disable html markup of errors
	ini_set('html_errors', 'on');

	# enable logging of errors
	ini_set('log_errors', 'on');

	# disable ignoring of repeat errors
	ini_set('ignore_repeated_errors', 'on');

	# disable ignoring of unique source errors
	ini_set('ignore_repeated_source', 'on');

	# enable logging of php memory leaks
	ini_set('report_memleaks', 'on');

	# preserve most recent error via php_errormsg
	ini_set('track_errors', 'on');

	# disable formatting of error reference links
	ini_set('docref_root', 0);

	# disable formatting of error reference links
	ini_set('docref_ext', 0);

	# specify path to php error log
	//ini_set('error_log', $config['logs_path'].'/'.$config['error_log_file']);

	# specify recording of all php errors
	ini_set('error_reporting', -1); // All
	//ini_set('error_reporting', 8191); // Complete error reporting
	//ini_set('error_reporting', 128); // Zend error reporting
	//ini_set('error_reporting', 8); // Basic error reporting
	//ini_set('error_reporting', 1); // Minimal error reporting
	//ini_set('error_reporting', E_ALL); //
	//ini_set('error_reporting', E_ALL & ~E_DEPRECATED); // all errors other than E_DEPRECATED
	//ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED); // all errors other than E_NOTICE and E_DEPRECATED

	# disable max error string length
	ini_set('log_errors_max_len', 0);
} else {
	# PHP error handling for production servers

	# disable display of startup errors
	ini_set('display_startup_errors', 'off');

	# disable display of all other errors
	ini_set('display_errors', 'off');

	# disable html markup of errors
	ini_set('html_errors', 'off');

	# enable logging of errors
	ini_set('log_errors', 'on');

	# disable ignoring of repeat errors
	ini_set('ignore_repeated_errors', 'on');

	# disable ignoring of unique source errors
	ini_set('ignore_repeated_source', 'on');

	# enable logging of php memory leaks
	ini_set('report_memleaks', 'on');

	# preserve most recent error via php_errormsg
	ini_set('track_errors', 'on');
	
	# disable formatting of error reference links
	ini_set('docref_root', 0);
	
	# disable formatting of error reference links
	ini_set('docref_ext', 0);
	
	# specify path to php error log
	//ini_set('error_log', $config['logs_path'].'/'.$config['error_log_file']);
	
	# specify recording of all php errors
	//ini_set('error_reporting', -1); // All
	//ini_set('error_reporting', 8191); // Complete error reporting
	//ini_set('error_reporting', 128); // Zend error reporting
	//ini_set('error_reporting', 8); // Basic error reporting
	//ini_set('error_reporting', 1); // Minimal error reporting
	//ini_set('error_reporting', E_ALL); //
	//ini_set('error_reporting', E_ALL & ~E_DEPRECATED); // all errors other than E_DEPRECATED
	ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_DEPRECATED); // all errors other than E_NOTICE and E_DEPRECATED
	
	# disable max error string length
	//ini_set('log_errors_max_len', 0);
}

date_default_timezone_set($app->settings['timezone']);
//ini_set('date.timezone', $app->settings['timezone']);

//http://wordpress.stackexchange.com/a/5352
if (in_array(PHP_SAPI, ['cli', 'cli-server'])) {
	if(array_key_exists(PHP_SAPI, $app->settings['php_sapi'])) {
		putenv("HTTP_HOST=".$app->settings['php_sapi'][PHP_SAPI]['HTTP_HOST']);
		putenv("SERVER_NAME=".$app->settings['php_sapi'][PHP_SAPI]['SERVER_NAME']);
		putenv("REQUEST_URI=".$app->settings['php_sapi'][PHP_SAPI]['REQUEST_URI']);
		putenv("REQUEST_METHOD=".$app->settings['php_sapi'][PHP_SAPI]['REQUEST_METHOD']);
	}
}