<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\JobInitializers;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\AlwaysModifiedInitializer;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;
use Orchestra\Testbench\TestCase;

class AlwaysModifiedInitializerTest extends TestCase
{
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\StickinessManager
     */
    protected $stickiness;

    /**
     * @var \Illuminate\Database\DatabaseManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $db;

    /**
     * @var \Illuminate\Database\Connection|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $connection;

    /**
     * @var \Illuminate\Queue\Events\JobProcessing
     */
    protected $jobProcessingEvent;

    /**
     * @var \Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated
     */
    protected $connectionCreatedEvent;

    /**
     * @var \Illuminate\Contracts\Queue\Job|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $job;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\AlwaysModifiedInitializer
     */
    protected $initializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stickiness = Mockery::mock(StickinessManager::class);
        $this->db = Mockery::mock(DatabaseManager::class);
        $this->connection = Mockery::mock(Connection::class);
        $this->job = Mockery::mock(Job::class);
        $this->jobProcessingEvent = new JobProcessing('foo', $this->job);
        $this->connectionCreatedEvent = new ConnectionCreated($this->connection);

        $this->initializer = Mockery::mock(AlwaysModifiedInitializer::class . '[shouldAssumeModified,shouldAssumeFresh]', [
            $this->stickiness,
            $this->db,
        ]);
    }

    public function testInitializeOnResolvedConnectionsWithFreshJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnTrue();
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('setRecordModificationState')->once()->with(false);

        $this->initializer->initializeOnResolvedConnections($this->jobProcessingEvent);
    }

    public function testInitializeOnResolvedConnectionsWithModifiedJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnFalse();
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('setRecordModificationState')->once()->with(true);

        $this->initializer->initializeOnResolvedConnections($this->jobProcessingEvent);
    }

    public function testInitializeOnResolvedConnectionsWithGeneralJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnNull();
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('setRecordModificationState')->once()->with(true);

        $this->initializer->initializeOnResolvedConnections($this->jobProcessingEvent);
    }

    public function testInitializeOnNewConnectionWithFreshJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnTrue();
        $this->connection->shouldReceive('setRecordModificationState')->once()->with(false);

        $this->initializer->initializeOnNewConnection($this->jobProcessingEvent, $this->connectionCreatedEvent);
    }

    public function testInitializeOnNewConnectionWithModifiedJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnFalse();
        $this->connection->shouldReceive('setRecordModificationState')->once()->with(true);

        $this->initializer->initializeOnNewConnection($this->jobProcessingEvent, $this->connectionCreatedEvent);
    }

    public function testInitializeOnNewConnectionWithGeneralJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnNull();
        $this->connection->shouldReceive('setRecordModificationState')->once()->with(true);

        $this->initializer->initializeOnNewConnection($this->jobProcessingEvent, $this->connectionCreatedEvent);
    }
}
