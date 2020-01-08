<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;

class ApplyingJobInitialization
{
    /**
     * @var \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager
     */
    protected $stickiness;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * @var \Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface
     */
    protected $job;

    /**
     * @var bool[]
     */
    protected $recordsModifiedStates = [];

    /**
     * ApplyingJobInitialization constructor.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager                       $stickiness
     * @param \Illuminate\Database\DatabaseManager                                          $db
     * @param \Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface $initializer
     */
    public function __construct(StickinessManager $stickiness, DatabaseManager $db, JobInitializerInterface $initializer)
    {
        $this->stickiness = $stickiness;
        $this->db = $db;
        $this->job = $initializer;
    }

    /**
     * Initialize database stickiness state on already resolved connections before processing each job.
     *
     * @param  \Illuminate\Queue\Events\JobProcessing $event
     * @return $this
     */
    public function initializeOnResolvedConnections(JobProcessing $event)
    {
        foreach ($this->db->getConnections() as $connection) {
            /* @var \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection */
            $this->recordsModifiedStates[$connection->getName()] = $this->stickiness->getRecordsModified($connection);
        }

        $this->job->initializeOnResolvedConnections($event);

        return $this;
    }

    /**
     * Initialize database stickiness state on newly created connection before processing each job.
     *
     * @param  \Illuminate\Queue\Events\JobProcessing                         $jobProcessingEvent
     * @param  \Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated $connectionCreatedEvent
     * @return $this
     */
    public function initializeOnNewConnection(JobProcessing $jobProcessingEvent, ConnectionCreated $connectionCreatedEvent)
    {
        $connection = $connectionCreatedEvent->connection;

        $this->recordsModifiedStates[$connection->getName()] = $this->stickiness->getRecordsModified($connection);

        $this->job->initializeOnNewConnection($jobProcessingEvent, $connectionCreatedEvent);

        return $this;
    }

    /**
     * Avoid revoking stickiness state initialization when DB::recordsHaveBeenModified() called on the Job execution.
     *
     * @param  \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     * @return $this
     */
    public function dontRevokeEffectsOn(ConnectionInterface $connection)
    {
        unset($this->recordsModifiedStates[$connection->getName()]);

        return $this;
    }

    /**
     * Revoke database stickiness state initialization after processing each job.
     */
    public function __destruct()
    {
        foreach ($this->db->getConnections() as $connection) {
            /* @var \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection */
            if (null !== $state = $this->recordsModifiedStates[$connection->getName()] ?? null) {
                $this->stickiness->setRecordsModified($connection, $state);
            }
        }
    }
}
