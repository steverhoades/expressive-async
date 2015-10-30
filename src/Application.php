<?php
namespace ExpressiveAsync;

use ExpressiveAsync\Emitter\AsyncEmitter;
use Zend\Expressive\Application as ExpressiveApplication;

class Application extends ExpressiveApplication
{
    private $emitter;

    public function getApplicationFromConnection($connection)
    {
        $newapp = clone $this;
        $newapp->emitter = new AsyncEmitter($connection);;

        return $newapp;
    }

    public function getEmitter()
    {
        return $this->emitter;
    }
}
