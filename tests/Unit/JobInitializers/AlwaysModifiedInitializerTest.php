<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\JobInitializers;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
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
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $connection;

    /**
     * @var \Illuminate\Queue\Events\JobProcessing
     */
    protected $event;

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
        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->job = Mockery::mock(Job::class);
        $this->event = new JobProcessing('foo', $this->job);

        $this->initializer = Mockery::mock(AlwaysModifiedInitializer::class . '[shouldAssumeModified,shouldAssumeFresh]', [
            $this->stickiness,
            $this->db,
        ]);
    }

    public function testFreshJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnTrue();
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsFresh')->once()->with($this->connection);

        $this->initializer->initializeStickinessState($this->event);
    }

    public function testModifiedJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnFalse();
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsModified')->once()->with($this->connection);

        $this->initializer->initializeStickinessState($this->event);
    }

    public function testGeneralJob(): void
    {
        $this->initializer->shouldReceive('shouldAssumeFresh')->once()->with($this->job)->andReturnNull();
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsModified')->once()->with($this->connection);

        $this->initializer->initializeStickinessState($this->event);
    }
}
