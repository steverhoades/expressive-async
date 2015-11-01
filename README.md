[![Build Status](https://travis-ci.org/steverhoades/expressive-async.svg?branch=master)](https://travis-ci.org/steverhoades/expressive-async)

# Overview
This library was created to experiment with zend expressive using React PHP and is based on React\Http.  Mileage may vary.

A large portion of this code leverages existing libraries such as guzzlehttp's psr7 library, zend diactoros, zend expressive, zend stratigility and zend service manager.

## Requirements
* PHP 5.6+

## Installation
    $ composer install

## Usage
This library alters the functionality of Zend\Expressive\Application slightly to allow for deferred processing from middleware.  If a middleware application returns an instance of DeferredResponse the application will wait to emit until the promise has been resolved.  DeferredResponse must be created with a React\Promise\Promise object.

Example:
```
function($request, $response) use ($eventLoop) {
    // create a request, wait 1-5 seconds and then return a response.
    $deferred = new Deferred();
    $eventLoop->addTimer(rand(1, 5), function() use ($deferred){
        echo 'Timer executed' . PHP_EOL;
        $deferred->resolve(new Diactoros\Response\HtmlResponse('Deferred response.'));
    });

    return new \ExpressiveAsync\DeferredResponse($deferred->promise());
}
```

## Example

```
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

// Example of a regular request
$router->addRoute(new \Zend\Expressive\Router\Route(
    '/',
    function($request, $response) use ($eventLoop) {
        return new Diactoros\Response\HtmlResponse('Hello World.');
    },
    ['GET'],
    'home'
));

// Example of a deferred request
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
$connectionHandler = new ExpressiveAsync\ExpressiveConnectionHandler($application);
$httpServer->on('request', $connectionHandler);
$socketServer->listen('10091');
$eventLoop->run();
```

## Run the Example
An example server has been provided. To run simply execute the following in the terminal from the root directory.

```
    $ php bin/server.php
```

Open your browser to http://127.0.0.1:10091

## More Information
For additional information on how to get started see the [zend-expressive](https://github.com/zendframework/zend-expressive) github page.

## Notes
Currently this example contains BufferedStream, which isn't using streams at all.  The Response and Request objects created by zend and guzzle all use php://memory or php://temp, I am currently unsure as to the impacts this will have in an environment that we wish to stay non-blocking.

