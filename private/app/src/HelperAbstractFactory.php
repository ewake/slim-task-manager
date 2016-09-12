<?php
namespace App;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HelperAbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $locator, $name, $requestedName)
    {
        if (substr($requestedName, -6) == 'Helper') {
            // This abstract factory will create any class that ends with the word "Action"
            return true;
        }

        return false;
    }
 
    public function createServiceWithName(ServiceLocatorInterface $locator, $name, $requestedName)
    {
        $className = $requestedName;
        
        return new $className($locator);
    }
}
