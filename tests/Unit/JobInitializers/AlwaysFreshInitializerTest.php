<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\JobInitializers;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\AlwaysFreshInitializer;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;
use Orchestra\Testbench\TestCase;

class AlwaysFreshInitializerTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->stickiness = Mockery::mock(StickinessManager::class);
        $this->db = Mockery::mock(DatabaseManager::class);
        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->job = Mockery::mock(Job::class);
        $this->event = new JobProcessing('foo', $this->job);
    }

    public function testGeneralJob(): void
    {
        $job = new class() {
        };

        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [
                'commandName' => get_class($job),
            ],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsFresh')->once()->with($this->connection);

        (new AlwaysFreshInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testFreshJob(): void
    {
        $job = new class() implements \Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeFresh {
        };

        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [
                'commandName' => get_class($job),
            ],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsFresh')->once()->with($this->connection);

        (new AlwaysFreshInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testModifiedJob(): void
    {
        $job = new class() implements \Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeModified {
        };

        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [
                'commandName' => get_class($job),
            ],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsModified')->once()->with($this->connection);

        (new AlwaysFreshInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testBrokenCommandNameJob(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [
                'commandName' => ['foo'],
            ],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsFresh')->once()->with($this->connection);

        (new AlwaysFreshInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testMissingCommandNameJob(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsFresh')->once()->with($this->connection);

        (new AlwaysFreshInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }
}
