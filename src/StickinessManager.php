<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface;

/**
 * Class StickinessManager
 */
class StickinessManager
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * StickinessManager constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param \Illuminate\Database\DatabaseManager      $db
     */
    public function __construct(Container $container, DatabaseManager $db)
    {
        $this->container = $container;
        $this->db = $db;
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * Set DB::$recordsModified state to $bool on $connection.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param bool                            $bool
     * @deprecated Directly use Connection::setRecordModificationState().
     * @codeCoverageIgnore
     */
    public function setRecordsModified(Connection $connection, bool $bool = true): void
    {
        $connection->setRecordModificationState($bool);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * Get DB::$recordsModified state on $connection.
     *
     * @param  \Illuminate\Database\Connection $connection
     * @return bool
     * @deprecated Directly use Connection::hasModifiedRecords().
     * @codeCoverageIgnore
     */
    public function getRecordsModified(Connection $connection): bool
    {
        return $connection->hasModifiedRecords();
    }

    /**
     * Set DB::$recordsModified state to false on $connection.
     *
     * @param \Illuminate\Database\Connection $connection
     * @deprecated Directly use Connection::setRecordsModified().
     * @codeCoverageIgnore
     */
    public function setRecordsFresh(Connection $connection): void
    {
        $connection->setRecordModificationState(false);
    }

    /**
     * Resolve DB::$recordsModified state via StickinessResolver on $connection.
     *
     * @param \Illuminate\Database\Connection $connection
     */
    public function resolveRecordsModified(Connection $connection): void
    {
        if (!$connection->hasModifiedRecords() && $this->isRecentlyModified($connection)) {
            $connection->setRecordModificationState(true);
        }
    }

    /**
     * Remembers that a write operation has been performed on the connection.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     */
    public function markAsModified(ConnectionInterface $connection): void
    {
        $this->stickinessResolver()->markAsModified($connection);
    }

    /**
     * Judges whether there was a write operation in the recent requests on the connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface $connection
     * @return bool
     */
    public function isRecentlyModified(ConnectionInterface $connection): bool
    {
        return $this->stickinessResolver()->isRecentlyModified($connection);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * Start Initializing database stickiness states before processing each job.
     *
     * @param  \Illuminate\Queue\Events\JobProcessing                          $event
     * @return \Mpyw\LaravelCachedDatabaseStickiness\ApplyingJobInitialization
     */
    public function startInitializingJob(JobProcessing $event): ApplyingJobInitialization
    {
        return (new ApplyingJobInitialization($this, $this->db, $this->jobInitializer()))
            ->initializeOnResolvedConnections($event);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @return \Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface
     */
    protected function stickinessResolver(): StickinessResolverInterface
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->container->make(StickinessResolverInterface::class);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @return \Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface
     */
    protected function jobInitializer(): JobInitializerInterface
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->container->make(JobInitializerInterface::class);
    }
}
