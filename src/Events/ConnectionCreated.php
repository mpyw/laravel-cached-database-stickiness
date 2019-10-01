<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Events;

use Illuminate\Database\ConnectionInterface;

/**
 * Class ConnectionCreated
 *
 * Dispatched on ConnectionFactory::make() calls.
 */
class ConnectionCreated
{
    /**
     * @var \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface
     */
    public $connection;

    /**
     * ConnectionCreated constructor.
     *
     * @param \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }
}
