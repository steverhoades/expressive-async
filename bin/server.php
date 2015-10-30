<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Zend\Diactoros;
use ExpressiveAsync\Server;
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use ExpressiveAsync\Application;

$serviceManager = new \Zend\ServiceManager\ServiceManager();
$eventLoop      = Factory::create();
$socketServer   = new SocketServer($eventLoop);
$httpServer     = new Server($socketServer);

$serviceManager->setFactory('EventLoop',function() use ($eventLoop) { return $eventLoop; });
$serviceManager->setInvokableClass(
    'Zend\Expressive\Router\RouterInterface',
    'Zend\Expressive\Router\FastRouteRouter'
);

$router = new \Zend\Expressive\Router\FastRouteRouter();
$route = new \Zend\Expressive\Router\Route(
    '/',
    function($request, $response) {
        return new Diactoros\Response\HtmlResponse('Hello World.');
    },
    ['GET'],
    'home'
);

$router->addRoute($route);
$application = new Application(
    $router,
    $serviceManager,
    function($request, $response) {
        return new Diactoros\Response\HtmlResponse('Not Found.', 404);
    }
);

$httpServer->on('request', new ExpressiveAsync\ExpressiveConnectionHandler($application));
$socketServer->listen('10091');
$eventLoop->run();
