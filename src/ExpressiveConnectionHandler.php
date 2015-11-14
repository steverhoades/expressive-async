<?php

namespace ExpressiveAsync;

use Evenement\EventEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Socket\ConnectionInterface;

/**
 * Class ExpressiveConnectionHandler
 *
 * This class handles incoming connections to the application server and starts a cloned version of the
 * application for each new connection.
 *
 * @emits EVENT_REQUEST             when a request is first made this event is triggered with
 * @emits EVENT_END                 when a request is ended, but before it is sent to the connection this event is fired
 * @emits EVENT_CONNECTION_END      sent when the connection has been instructed its going to end
 * @emits EVENT_CONNECTION_CLOSE    sent when the connection is closing
 *
 * @package ExpressiveAsync
 */
class ExpressiveConnectionHandler extends EventEmitter
{

    const EVENT_REQUEST = 'request';
    const EVENT_END = 'end';
    const EVENT_CONNECTION_END = 'connection.end';
    const EVENT_CONNECTION_CLOSE = 'connection.close';

    protected $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application  = $application;
    }

    /**
     * @param ConnectionInterface $conn
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __invoke(ConnectionInterface $conn, ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->emit('request', [$conn, &$request, &$response]);

        $application = $this->application->getApplicationForConnection($conn);
        $application->on('end', function($request, &$response) use ($conn, $application){
            $this->emit('end', [$conn, $request, &$response]);
        });

        $conn->on('end', function() use ($conn) {
            $this->emit('connection.end', [$conn]);
        });

        $conn->on('close', function() use ($conn) {
            $this->emit('connection.close', [$conn]);
        });

        $application->run($request, $response);
    }
}
