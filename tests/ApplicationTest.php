<?php
namespace ExpressiveAsync\Test;

use ExpressiveAsync\Application;
use PHPUnit_Framework_TestCase as TestCase;

class ApplicationTest extends TestCase
{
    public function setUp()
    {
        $this->noopMiddleware = function ($req, $res, $next) {
        };

        $this->router = $this->getMock('Zend\Expressive\Router\RouterInterface');
    }

    public function getApp()
    {
        return new Application($this->router);
    }

    public function testGetApplicationForConnection()
    {
        $conn = new ConnectionStub();
        $application = $this->getApp();

        $application = $application->getApplicationForConnection($conn);
        $this->assertInstanceOf('ExpressiveAsync\Application', $application);
    }

    public function getEmitterIsAsyncEmitter()
    {
        $conn = new ConnectionStub();
        $application = $this->getApp();

        $application = $application->getApplicationForConnection($conn);
        $this->assertInstanceOf('ExpressiveAsync\AsyncEmitter', $application->getEmitter());
    }
}
