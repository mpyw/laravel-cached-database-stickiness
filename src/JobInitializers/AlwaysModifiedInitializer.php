<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\Concerns\DetectsInterfaces;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\Concerns\RevokesInitializeEffects;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;

/**
 * Class AlwaysModifiedInitializer
 *
 * Always set DB::$recordsModified to true except when the Job class implements ShouldAssumeFresh.
 */
class AlwaysModifiedInitializer implements JobInitializerInterface
{
    use DetectsInterfaces, RevokesInitializeEffects;

    /**
     * @var \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager
     */
    protected $stickiness;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * AlwaysModifiedInitializer constructor.
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
     * {@inheritdoc}
     */
    public function initializeOnResolvedConnections(JobProcessing $event): void
    {
        $this->syncRecordsModifiedStates($this->db->getConnections());

        if ($this->shouldAssumeFresh($event->job)) {
            foreach ($this->db->getConnections() as $connection) {
                $this->stickiness->setRecordsFresh($connection);
            }
            return;
        }

        foreach ($this->db->getConnections() as $connection) {
            $this->stickiness->setRecordsModified($connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initializeOnNewConnection(JobProcessing $jobProcessingEvent, ConnectionCreated $connectionCreatedEvent): void
    {
        $this->syncRecordsModifiedStates([$connectionCreatedEvent->connection]);

        $this->shouldAssumeFresh($jobProcessingEvent->job)
            ? $this->stickiness->setRecordsFresh($connectionCreatedEvent->connection)
            : $this->stickiness->setRecordsModified($connectionCreatedEvent->connection);
    }
}
