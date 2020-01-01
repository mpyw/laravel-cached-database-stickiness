<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Queue\Events\JobProcessing;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface;
use ReflectionProperty;

/**
 * Class StickinessManager
 */
class StickinessManager
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * StickinessManager constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Called when JobProcessing dispatched.
     *
     * @param \Illuminate\Queue\Events\JobProcessing $event
     */
    public function onJobProcessing(JobProcessing $event): void
    {
        $this->initializeStickinessState($event);
    }

    /**
     * Called when RecordsHaveBeenModified dispatched.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified $event
     */
    public function onRecordsHaveBeenModified(RecordsHaveBeenModified $event): void
    {
        $this->markAsModified($event->connection);
    }

    /**
     * Called when ConnectionCreated dispatched.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated $event
     */
    public function onConnectionCreated(ConnectionCreated $event): void
    {
        $this->resolveRecordsModified($event->connection);
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
        if ($this->isRecentlyModified($connection)) {
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

    /**
     * Initialize database stickiness state before processing each job.
     *
     * @param JobProcessing $event
     */
    public function initializeStickinessState(JobProcessing $event): void
    {
        $this->jobInitializer()->initializeStickinessState($event);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @return StickinessResolverInterface
     */
    protected function stickinessResolver(): StickinessResolverInterface
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->container->make(StickinessResolverInterface::class);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @return JobInitializerInterface
     */
    protected function jobInitializer(): JobInitializerInterface
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->container->make(JobInitializerInterface::class);
    }
}
