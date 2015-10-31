<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/29/15
 * Time: 3:30 PM
 */

namespace ExpressiveAsync;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Socket\ConnectionInterface;

class ExpressiveConnectionHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application  = $application;
    }

    public function __invoke(ConnectionInterface $conn, ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $this->application->getApplicationForConnection($conn);
        $application->run($request, $response);
    }
}
