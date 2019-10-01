<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\JobInitializers;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\AlwaysModifiedInitializer;
use Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeModified;
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
        $this->stickiness->shouldReceive('setRecordsModified')->once()->with($this->connection);

        (new AlwaysModifiedInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testFreshJob(): void
    {
        $job = new class() implements ShouldAssumeFresh {
        };

        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [
                'commandName' => get_class($job),
            ],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsFresh')->once()->with($this->connection);

        (new AlwaysModifiedInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testModifiedJob(): void
    {
        $job = new class() implements ShouldAssumeModified {
        };

        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [
                'commandName' => get_class($job),
            ],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsModified')->once()->with($this->connection);

        (new AlwaysModifiedInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testBrokenCommandNameJob(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [
                'commandName' => ['foo'],
            ],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsModified')->once()->with($this->connection);

        (new AlwaysModifiedInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }

    public function testMissingCommandNameJob(): void
    {
        $this->job->shouldReceive('payload')->once()->andReturn([
            'data' => [],
        ]);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->stickiness->shouldReceive('setRecordsModified')->once()->with($this->connection);

        (new AlwaysModifiedInitializer($this->stickiness, $this->db))->initializeStickinessState($this->event);
    }
}
