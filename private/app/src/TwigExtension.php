<?php
namespace App;

//class TwigExtension extends \Twig_Extension
class TwigExtension extends \Slim\Views\TwigExtension
{
	private $container;
	
	public function __construct($container)
	{
		$this->container = $container;
		
		parent::__construct($this->container->get('router'), $this->container->get('request')->getUri());
	}
	
	public function getName()
	{
		return 'App';
	}
	
	public function getFilters()
	{
		return array_merge(parent::getFilters(), [
				new \Twig_SimpleFilter('rot13', 'str_rot13'),
				//new \Twig_SimpleFilter('obfuscate', 'App\Helper\UtilHelper:obfuscate'),
		]);
	}
	
	public function getFunctions()
	{
		return array_merge(parent::getFunctions(), [
				new \Twig_SimpleFunction('is_array', 'is_array'),
				new \Twig_SimpleFunction('base_path', array($this, 'basePath')),
				new \Twig_SimpleFunction('current_path', array($this, 'currentPath')),
				new \Twig_SimpleFunction('current_url', array($this, 'currentUrl')),
				new \Twig_SimpleFunction('cdn_url', array($this, 'cdnUrl')),	
				new \Twig_SimpleFunction('version', array($this, 'version')),
		]);
	}
	
	public function basePath()
	{
		$request = $this->container->get('request');
		$uri =  $request->getUri();
	
		return $uri->getBasePath();
	}
	
	public function currentPath()
	{
		$request = $this->container->get('request');
		$uri =  $request->getUri();

		return $uri->getBasePath(). '/' . ltrim($uri->getPath(), '/');
	}
	
	public function currentUrl()
	{
		$request = $this->container->get('request');

		return $request->getUri();
	}
	
	public function cdnUrl()
	{
		$settings = $this->container->get('settings');
		
		if (isset($settings['cdn_url'])) {
			return $settings['cdn_url'];
		}
		
		return $this->baseUrl();
	}
	
	public function version($path)
	{
		$file = _PUBLIC.'/'.$path;
	
		if(file_exists($file)){
			$parts = explode( '.', $path);
			$extension = array_pop($parts);
			array_push($parts, filemtime($file), $extension);
			$path = implode('.', $parts);
				
			return $path;
		} else {
			throw new \Exception(sprintf(_('The file "%1$s" cannot be found in the public folder'), $path));
		}
	}
}
