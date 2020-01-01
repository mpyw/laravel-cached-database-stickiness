<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers;

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
}
