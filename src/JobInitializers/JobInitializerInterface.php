<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;

/**
 * Interface JobInitializerInterface
 */
interface JobInitializerInterface
{
    /**
     * Initialize database stickiness state on already resolved connections before processing each job.
     *
     * @param \Illuminate\Queue\Events\JobProcessing $event
     */
    public function initializeOnResolvedConnections(JobProcessing $event): void;

    /**
     * Initialize database stickiness state on newly created connection before processing each job.
     *
     * @param \Illuminate\Queue\Events\JobProcessing                         $jobProcessingEvent
     * @param \Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated $connectionCreatedEvent
     */
    public function initializeOnNewConnection(JobProcessing $jobProcessingEvent, ConnectionCreated $connectionCreatedEvent): void;

    /**
     * Revoke database stickiness state initialization after processing each job.
     */
    public function revokeInitializeEffects(): void;

    /**
     * Avoid revoking stickiness state initialization when DB::recordsHaveBeenModified() called on the Job execution.
     *
     * @param \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     */
    public function dontRevokeEffectsOn(ConnectionInterface $connection): void;
}
