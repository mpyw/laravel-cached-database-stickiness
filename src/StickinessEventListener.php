<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified;

class StickinessEventListener
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
     * @var null|\Illuminate\Queue\Events\JobProcessing
     */
    protected $currentJobProcessingEvent;

    /**
     * @var bool[]
     */
    protected $currentRecordsModifiedStates = [];

    /**
     * StickinessEventListener constructor.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager $stickiness
     * @param \Illuminate\Database\DatabaseManager                    $db
     */
    public function __construct(StickinessManager $stickiness, DatabaseManager $db)
    {
        $this->stickiness = $stickiness;
        $this->db = $db;
    }

    /**
     * Called when JobProcessing dispatched.
     *
     * @param \Illuminate\Queue\Events\JobProcessing $event
     */
    public function onJobProcessing(JobProcessing $event): void
    {
        $this->currentJobProcessingEvent = $event;
        $this->stickiness->initializeStickinessState($event);
        $this->memoizeRecordsModified();
    }

    /**
     * Called when JobProcessed dispatched.
     *
     * @param \Illuminate\Queue\Events\JobProcessed $event
     */
    public function onJobProcessed(JobProcessed $event): void
    {
        $this->currentJobProcessingEvent = null;
        $this->restoreRecordsModified();
    }

    /**
     * Called when JobExceptionOccurred dispatched.
     *
     * @param \Illuminate\Queue\Events\JobExceptionOccurred $event
     */
    public function onJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        $this->currentJobProcessingEvent = null;
        $this->restoreRecordsModified();
    }

    /**
     * Called when JobFailed dispatched.
     *
     * @param \Illuminate\Queue\Events\JobFailed $event
     */
    public function onJobFailed(JobFailed $event): void
    {
        $this->currentJobProcessingEvent = null;
        $this->restoreRecordsModified();
    }

    /**
     * Called when ConnectionCreated dispatched.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated $event
     */
    public function onConnectionCreated(ConnectionCreated $event): void
    {
        if ($this->currentJobProcessingEvent) {
            $this->stickiness->initializeStickinessState($this->currentJobProcessingEvent, $event);
        }

        $this->stickiness->resolveRecordsModified($event->connection);
    }

    /**
     * Called when RecordsHaveBeenModified dispatched.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified $event
     */
    public function onRecordsHaveBeenModified(RecordsHaveBeenModified $event): void
    {
        $this->stickiness->markAsModified($event->connection);

        // Exclude from restoration targets
        unset($this->currentRecordsModifiedStates[$event->connection->getName()]);
    }

    /**
     * Memoize $recordsModified state on already resolved connections after state initialized by JobInitializer.
     */
    protected function memoizeRecordsModified(): void
    {
        $this->currentRecordsModifiedStates = [];
        foreach ($this->db->getConnections() as $connection) {
            /* @var \Illuminate\Database\ConnectionInterface|\Illuminate\Database\Connection $connection */
            $this->currentRecordsModifiedStates[$connection->getName()] = $this->stickiness->getRecordsModified($connection);
        }
    }

    /**
     * Restore $recordsModified state unless if its value has not been changed during Job execution.
     */
    protected function restoreRecordsModified(): void
    {
        foreach ($this->db->getConnections() as $connection) {
            /* @var \Illuminate\Database\ConnectionInterface|\Illuminate\Database\Connection $connection */
            if (null !== $recordsModified = $this->currentRecordsModifiedStates[$connection->getName()] ?? null) {
                $this->stickiness->setRecordsModified($connection, $recordsModified);
            }
        }
        $this->currentRecordsModifiedStates = [];
    }
}
