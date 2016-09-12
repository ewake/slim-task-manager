<?php
namespace App;

use RedBeanPHP\R;

class Action
{
	protected $container;
	
	public function __construct($container)
	{
		$this->container = $container;
	}
}
