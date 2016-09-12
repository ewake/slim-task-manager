<?php
namespace App;

class FlashMessages extends \Slim\Flash\Messages
{
    public function addNowMessage($key, $message)
    {
        //Create Array for this key
        if (!isset($this->fromPrevious[$key])) {
            $this->fromPrevious[$key] = array();
        }
        
        $this->fromPrevious[$key][] = (string)$message;
    }
}
