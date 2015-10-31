<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Zend\Diactoros;
use ExpressiveAsync\Server;
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use ExpressiveAsync\Application;
use React\Promise\Deferred;

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

$router->addRoute(new \Zend\Expressive\Router\Route(
    '/',
    function($request, $response) use ($eventLoop) {
        return new Diactoros\Response\HtmlResponse('Hello World.');
    },
    ['GET'],
    'home'
));


$router->addRoute(new \Zend\Expressive\Router\Route(
    '/deferred',
    function($request, $response) use ($eventLoop) {
        // create a request, wait 1-5 seconds and then return a response.
        $deferred = new Deferred();
        $eventLoop->addTimer(rand(1, 5), function() use ($deferred){
            echo 'Timer executed' . PHP_EOL;
            $deferred->resolve(new Diactoros\Response\HtmlResponse('Deferred response.'));
        });

        return new \ExpressiveAsync\DeferredResponse($deferred->promise());
    },
    ['GET'],
    'deferred'
));


$application = new Application(
    $router,
    $serviceManager,
    function($request, $response) {
        echo 'final handler was called.' . PHP_EOL;
        return new Diactoros\Response\HtmlResponse('Not Found.', 404);
    }
);

$httpServer->on('request', new ExpressiveAsync\ExpressiveConnectionHandler($application));
$socketServer->listen('10091');
$eventLoop->run();
