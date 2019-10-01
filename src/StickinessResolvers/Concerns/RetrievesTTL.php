<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\Concerns;

use Illuminate\Database\ConnectionInterface;

/**
 * Trait RetrievesTTL
 */
trait RetrievesTTL
{
    /**
     * Read the TTL value from the database configuration file.
     *
     * @param  \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     * @return int
     */
    protected function ttl(ConnectionInterface $connection): int
    {
        return $connection->getConfig('stickiness_ttl') ?? 5;
    }
}
