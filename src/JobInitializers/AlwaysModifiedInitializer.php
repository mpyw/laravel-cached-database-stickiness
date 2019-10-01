<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;

/**
 * Class AlwaysModifiedInitializer
 *
 * Always set DB::$recordsModified to true except when the Job class implements ShouldAssumeFresh.
 */
class AlwaysModifiedInitializer implements JobInitializerInterface
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
    public function initializeStickinessState(JobProcessing $event): void
    {
        $command = (string)filter_var($event->job->payload()['data']['commandName'] ?? null);

        if (is_subclass_of($command, ShouldAssumeFresh::class)) {
            foreach ($this->db->getConnections() as $connection) {
                $this->stickiness->setRecordsFresh($connection);
            }
            return;
        }

        foreach ($this->db->getConnections() as $connection) {
            $this->stickiness->setRecordsModified($connection);
        }
    }
}
