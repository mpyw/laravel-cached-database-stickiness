<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\Concerns;

use Illuminate\Database\ConnectionInterface;

/**
 * Trait RevokesInitializeEffects
 *
 * @property \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager $stickiness
 * @property \Illuminate\Database\DatabaseManager                    $db
 */
trait RevokesInitializeEffects
{
    /**
     * @var bool[]
     */
    protected $recordsModifiedStates = [];

    /**
     * Sync database stickiness state before processing each job.
     *
     * @param \Illuminate\Database\Connection[]|\Illuminate\Database\ConnectionInterface[] $connections
     */
    public function syncRecordsModifiedStates(array $connections): void
    {
        foreach ($connections as $connection) {
            /* @var \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection */
            $this->recordsModifiedStates[$connection->getName()] = $this->stickiness->getRecordsModified($connection);
        }
    }

    /**
     * Revoke database stickiness state initialization after processing each job.
     */
    public function revokeInitializeEffects(): void
    {
        foreach ($this->db->getConnections() as $connection) {
            /* @var \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection */
            $this->recordsModifiedStates[$connection->getName()] = $this->stickiness->getRecordsModified($connection);
        }

        $this->recordsModifiedStates = [];
    }

    /**
     * Avoid revoking stickiness state initialization when DB::recordsHaveBeenModified() called on the Job execution.
     *
     * @param \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     */
    public function dontRevokeEffectsOn(ConnectionInterface $connection): void
    {
        unset($this->recordsModifiedStates[$connection->getName()]);
    }
}
