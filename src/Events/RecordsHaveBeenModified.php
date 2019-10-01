<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Events;

use Illuminate\Database\ConnectionInterface;

/**
 * Class RecordsHaveBeenModified
 *
 * Dispatched on DB::recordsHaveBeenModified() calls.
 */
class RecordsHaveBeenModified
{
    /**
     * @var \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface
     */
    public $connection;

    /**
     * RecordsHaveBeenModified constructor.
     *
     * @param \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }
}
