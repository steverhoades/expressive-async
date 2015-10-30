<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/27/15
 * Time: 9:18 PM
 */

namespace ExpressiveAsync;

use Evenement\EventEmitter;
use React\Socket\ServerInterface as SocketServerInterface;
use React\Socket\ConnectionInterface;
use React\Http\ServerInterface;

class Server extends EventEmitter implements ServerInterface
{
    private $io;

    public function __construct(SocketServerInterface $io)
    {
        $this->io = $io;
        $this->io->on('connection', array($this, 'handleNewConnection'));
    }

    /**
     * On a new connection instantiate a new RequestParser and listen for the data event until such time as all headers
     * are parsed.  When this happens a Request object will be generated and Server::handleRequest will be called.
     *
     * @param ConnectionInterface $conn
     */
    protected function handleNewConnection(ConnectionInterface $conn)
    {
        $parser = new RequestParser();
        $parser->on('headers', function (ServerRequest $request) use ($conn, $parser) {
            // attach remote ip to the request as metadata
            $request = $request->withConnection($conn);

            $conn->removeListener('data', array($parser, 'feed'));
            $this->handleRequest($conn, $request);

        });

        $conn->on('data', array($parser, 'feed'));
    }

    /**
     * @param ConnectionInterface $conn
     * @param Request $request
     * @param $bodyBuffer
     */
    public function handleRequest(ConnectionInterface $conn, ServerRequest $request)
    {
        $response = new Response();
        $response = $response->withConnection($conn);

        if (!$this->listeners('request')) {
            $conn->end();

            return;
        }

        $this->emit('request', array($conn, $request, $response));
    }
}
