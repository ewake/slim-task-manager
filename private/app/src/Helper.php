<?php
namespace App;

use RedBeanPHP\R;

class Helper
{
	protected $container;
	
	public function __construct($container)
	{
		$this->container = $container;
	}
}
