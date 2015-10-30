Overview
====================
This library was created to experiment with zend expressive using React PHP and is based on React\Http.  Mileage may vary.

A large portion of this code leverages existing libraries such as guzzlehttp's psr7 library, zend diactoros, zend expressive, zend stratigility and zend service manager.

Requirements
--------------------
* PHP 5.6+

Installation
--------------------
    $ composer install
    
Example
------------------

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

    
Run the Example
--------------------
An example server has been provided. To run simply execute the following in the terminal from the root directory.

    $ php bin/server.php

Open your browser to http://127.0.0.1:10091

More Information
---------------------
For additional information on how to get started see the [zend-expressive](https://github.com/zendframework/zend-expressive) github page.

Notes
-------------
Currently this example contains BufferedStream, which isn't using streams at all.  The Response and Request objects created by zend and guzzle all use php://memory or php://temp, I am currently unsure as to the impacts this will have in an environment that we wish to stay non-blocking.

