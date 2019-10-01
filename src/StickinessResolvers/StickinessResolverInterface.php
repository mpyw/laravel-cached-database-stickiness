<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers;

use Illuminate\Database\ConnectionInterface;

/**
 * Interface StickinessResolverInterface
 */
interface StickinessResolverInterface
{
    /**
     * Remembers that a write operation has been performed on the connection.
     *
     * @param \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     */
    public function markAsModified(ConnectionInterface $connection): void;

    /**
     * Judges whether there was a write operation in the recent requests on the connection.
     *
     * @param  \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     * @return bool
     */
    public function isRecentlyModified(ConnectionInterface $connection): bool;
}
