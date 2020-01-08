<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

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
     * @var null|\Illuminate\Queue\Events\JobProcessing
     */
    protected $currentJobProcessingEvent;

    /**
     * @var \Mpyw\LaravelCachedDatabaseStickiness\ApplyingJobInitialization
     */
    protected $currentJobInitialization;

    /**
     * StickinessEventListener constructor.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager $stickiness
     */
    public function __construct(StickinessManager $stickiness)
    {
        $this->stickiness = $stickiness;
    }

    /**
     * Called when JobProcessing dispatched.
     *
     * @param \Illuminate\Queue\Events\JobProcessing $event
     */
    public function onJobProcessing(JobProcessing $event): void
    {
        $this->currentJobProcessingEvent = $event;
        $this->currentJobInitialization = $this->stickiness->startInitializingJob($event);
    }

    /**
     * Called when JobProcessed dispatched.
     *
     * @param \Illuminate\Queue\Events\JobProcessed $event
     */
    public function onJobProcessed(JobProcessed $event): void
    {
        $this->currentJobProcessingEvent = null;
        $this->currentJobInitialization = null;
    }

    /**
     * Called when JobExceptionOccurred dispatched.
     *
     * @param \Illuminate\Queue\Events\JobExceptionOccurred $event
     */
    public function onJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        $this->currentJobProcessingEvent = null;
        $this->currentJobInitialization = null;
    }

    /**
     * Called when JobFailed dispatched.
     *
     * @param \Illuminate\Queue\Events\JobFailed $event
     */
    public function onJobFailed(JobFailed $event): void
    {
        $this->currentJobProcessingEvent = null;
        $this->currentJobInitialization = null;
    }

    /**
     * Called when ConnectionCreated dispatched.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated $event
     */
    public function onConnectionCreated(ConnectionCreated $event): void
    {
        if ($this->currentJobProcessingEvent && $this->currentJobInitialization) {
            $this->currentJobInitialization->initializeOnNewConnection($this->currentJobProcessingEvent, $event);
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
        if ($this->currentJobInitialization) {
            $this->currentJobInitialization->dontRevokeEffectsOn($event->connection);
        }

        $this->stickiness->markAsModified($event->connection);
    }
}
