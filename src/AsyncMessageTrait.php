<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/29/15
 * Time: 4:16 PM
 */

namespace ExpressiveAsync;

use React\Socket\ConnectionInterface;

trait AsyncMessageTrait
{
    private $connection;

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param ConnectionInterface $connection
     * @return ServerRequest
     */
    public function withConnection(ConnectionInterface $connection)
    {
        $new = clone $this;
        $new->connection = $connection;

        return $new;
    }
}
