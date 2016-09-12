<?php
namespace App\Middleware;

class LangMiddleware extends \App\Middleware
{
	public function run($request, $response, $next)
	{
		$settings = $this->container->get('settings');
		$env = $this->container->get('environment');
		$logger = $this->container->get('logger');
	
		// setup default lang based on first in the list
		$langs_keys = array_keys($settings['langs']);
		$lang = $langs_keys[0];
	
		if(isset($_SESSION['lang']) && in_array($_SESSION['lang'], $langs_keys)) {
			$lang = $_SESSION['lang'];
		} elseif (isset($env['ACCEPT_LANGUAGE'])) {
			// try and auto-detect, find the language with the lowest offset as they are in order of priority
			$priority_offset = strlen($env['ACCEPT_LANGUAGE']);
	
			foreach($langs_keys as $langs_key) {
				$i = strpos($env['ACCEPT_LANGUAGE'], $langs_key);
				if ($i !== false && $i < $priority_offset) {
					$priority_offset = $i;
					$lang = $langs_key;
				}
			}
		}
	
		if ($env['PATH_INFO'] != '/') {
			$pathInfo = $env['PATH_INFO'] . (substr($env['PATH_INFO'], -1) !== '/' ? '/' : '');
	
			// extract lang from PATH_INFO
			foreach($langs_keys as $langs_key) {
				$match = '/'.$langs_key;
				if (strpos($pathInfo, $match.'/') === 0) {
					$lang = $langs_key;
				}
			}
		}
	
		$_SESSION['lang'] = $lang;
		$this->container['lang'] = $lang;
		
		$logger->addDebug('lang', array('lang' => $lang));
		
		// Set language
		putenv('LC_ALL='.$settings['langs'][$lang]['locale']);
		setlocale(LC_ALL, $settings['langs'][$lang]['locale']);
		
		// Specify the location of the translation tables
		bindtextdomain('messages', $settings['locale_path']);
		bind_textdomain_codeset('messages', $settings['charset']);
		
		// Choose domain
		textdomain('messages');
	
		$response = $next($request, $response);
	
		return $response;
	}
}