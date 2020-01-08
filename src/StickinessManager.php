<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface;
use ReflectionProperty;

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
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param bool                                     $bool
     */
    public function setRecordsModified(ConnectionInterface $connection, bool $bool = true): void
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'recordsModified');
        $property->setAccessible(true);
        $property->setValue($connection, $bool);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * Get DB::$recordsModified state on $connection.
     *
     * @param  \Illuminate\Database\ConnectionInterface $connection
     * @return bool
     */
    public function getRecordsModified(ConnectionInterface $connection): bool
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'recordsModified');
        $property->setAccessible(true);
        return (bool)$property->getValue($connection);
    }

    /**
     * Set DB::$recordsModified state to false on $connection.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     */
    public function setRecordsFresh(ConnectionInterface $connection): void
    {
        $this->setRecordsModified($connection, false);
    }

    /**
     * Resolve DB::$recordsModified state via StickinessResolver on $connection.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     */
    public function resolveRecordsModified(ConnectionInterface $connection): void
    {
        if (!$this->getRecordsModified($connection) && $this->isRecentlyModified($connection)) {
            $this->setRecordsModified($connection);
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
