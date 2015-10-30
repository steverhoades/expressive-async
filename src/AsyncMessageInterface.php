<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/29/15
 * Time: 4:14 PM
 */

namespace ExpressiveAsync;

use React\Socket\ConnectionInterface;

interface AsyncMessageInterface
{
    /**
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * @param ConnectionInterface $connection
     * @return ServerRequest
     */
    public function withConnection(ConnectionInterface $connection);
}
