<?php
namespace ExpressiveAsync;

use ExpressiveAsync\Emitter\AsyncEmitter;
use Zend\Expressive\Application as ExpressiveApplication;

/**
 * Class Application
 *
 * This class overloads the Zend\Expressive\Application class to insure that the response is writable to the current
 * connection.  It was necessary because the $emitter property is declared as private on Zend\Expressive\Application.
 *
 * @package ExpressiveAsync
 */
class Application extends ExpressiveApplication
{
    /**
     * @var AsyncEmitter
     */
    private $emitter;

    /**
     * For each new connection we need to return a new instance of AsyncEmitter on the Zend\Expressive\Application
     * object.  This is so that the response can be written to the correct connection.
     *
     * @param $connection
     * @return Application
     */
    public function getApplicationForConnection($connection)
    {
        $newapp = clone $this;
        $newapp->emitter = new AsyncEmitter($connection);;

        return $newapp;
    }

    /**
     * Overload the parent as we need the emitter on this application and not the parents.
     *
     * @return mixed
     */
    public function getEmitter()
    {
        return $this->emitter;
    }
}
