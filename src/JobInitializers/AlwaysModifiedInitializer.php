<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\Concerns\DetectsInterfaces;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;

/**
 * Class AlwaysModifiedInitializer
 *
 * Always set DB::$recordsModified to true except when the Job class implements ShouldAssumeFresh.
 */
class AlwaysModifiedInitializer implements JobInitializerInterface
{
    use DetectsInterfaces;

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
        $state = !($this->shouldAssumeFresh($event->job) ?? false);

        foreach ($this->db->getConnections() as $connection) {
            $connection->setRecordModificationState($state);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initializeOnNewConnection(JobProcessing $jobProcessingEvent, ConnectionCreated $connectionCreatedEvent): void
    {
        $connectionCreatedEvent->connection->setRecordModificationState(
            !($this->shouldAssumeFresh($jobProcessingEvent->job) ?? false),
        );
    }
}
