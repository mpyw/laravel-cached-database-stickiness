<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessEventListener;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class StickinessEventListenerTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->stickiness = Mockery::mock(StickinessManager::class);
        $this->db = Mockery::mock(DatabaseManager::class);
        $this->connection = Mockery::mock(ConnectionInterface::class);
    }

    protected function setCurrentJobProcessingEvent(StickinessEventListener $listener, ?JobProcessing $event): void
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($listener, 'currentJobProcessingEvent');
        $property->setAccessible(true);
        $property->setValue($listener, $event);
    }

    protected function getCurrentJobProcessingEvent(StickinessEventListener $listener): ?JobProcessing
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($listener, 'currentJobProcessingEvent');
        $property->setAccessible(true);
        return $property->getValue($listener);
    }

    protected function setCurrentRecordsModifiedStates(StickinessEventListener $listener, array $values)
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($listener, 'currentRecordsModifiedStates');
        $property->setAccessible(true);
        $property->setValue($listener, $values);
    }

    protected function getCurrentRecordsModifiedStates(StickinessEventListener $listener)
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($listener, 'currentRecordsModifiedStates');
        $property->setAccessible(true);
        return $property->getValue($listener);
    }

    public function testOnJobProcessing(): void
    {
        $event = Mockery::mock(JobProcessing::class);

        $this->stickiness->shouldReceive('initializeStickinessState')->once()->with($event);
        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('getName')->andReturn('foo');
        $this->stickiness->shouldReceive('getRecordsModified')->andReturnFalse();

        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertSame([], $this->getCurrentRecordsModifiedStates($listener));

        $listener->onJobProcessing($event);

        $this->assertSame($event, $this->getCurrentJobProcessingEvent($listener));
        $this->assertSame(['foo' => false], $this->getCurrentRecordsModifiedStates($listener));
    }

    public function testOnJobProcessedWithStateMemo(): void
    {
        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('getName')->andReturn('foo');
        $this->stickiness->shouldReceive('getRecordsModified')->andReturnFalse();
        $this->stickiness->shouldReceive('setRecordsModified')->andReturnFalse();

        $this->setCurrentJobProcessingEvent($listener, $event = Mockery::mock(JobProcessing::class));
        $this->setCurrentRecordsModifiedStates($listener, ['foo' => false]);

        $listener->onJobProcessed(Mockery::mock(JobProcessed::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertSame([], $this->getCurrentRecordsModifiedStates($listener));
    }

    public function testOnJobProcessedWithoutStateMemo(): void
    {
        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('getName')->andReturn('foo');
        $this->stickiness->shouldReceive('getRecordsModified')->andReturnFalse();
        $this->stickiness->shouldNotReceive('setRecordsModified');

        $this->setCurrentJobProcessingEvent($listener, $event = Mockery::mock(JobProcessing::class));
        $this->setCurrentRecordsModifiedStates($listener, []);

        $listener->onJobProcessed(Mockery::mock(JobProcessed::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertSame([], $this->getCurrentRecordsModifiedStates($listener));
    }

    public function testOnJobExceptionOccurred(): void
    {
        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('getName')->andReturn('foo');
        $this->stickiness->shouldReceive('getRecordsModified')->andReturnFalse();
        $this->stickiness->shouldReceive('setRecordsModified')->andReturnFalse();

        $this->setCurrentJobProcessingEvent($listener, $event = Mockery::mock(JobProcessing::class));
        $this->setCurrentRecordsModifiedStates($listener, ['foo' => false]);

        $listener->onJobExceptionOccurred(Mockery::mock(JobExceptionOccurred::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertSame([], $this->getCurrentRecordsModifiedStates($listener));
    }

    public function testOnJobFailed(): void
    {
        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->db->shouldReceive('getConnections')->once()->andReturn([$this->connection]);
        $this->connection->shouldReceive('getName')->andReturn('foo');
        $this->stickiness->shouldReceive('getRecordsModified')->andReturnFalse();
        $this->stickiness->shouldReceive('setRecordsModified')->andReturnFalse();

        $this->setCurrentJobProcessingEvent($listener, $event = Mockery::mock(JobProcessing::class));
        $this->setCurrentRecordsModifiedStates($listener, ['foo' => false]);

        $listener->onJobFailed(Mockery::mock(JobFailed::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertSame([], $this->getCurrentRecordsModifiedStates($listener));
    }

    public function onConnectionCreatedWithCurrentJobProcessingEvent(): void
    {
        $job = Mockery::mock(Job::class);
        $connection = Mockery::mock(Connection::class);

        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->setCurrentJobProcessingEvent($listener, $event = new JobProcessing('foo', $job));

        $this->stickiness->shouldReceive('initializeStickinessState')->once()->with($event);
        $this->stickiness->shouldReceive('resolveRecordsModified')->once();

        $listener->onConnectionCreated(new ConnectionCreated($connection));
    }

    public function onConnectionCreatedWithoutCurrentJobProcessingEvent(): void
    {
        $connection = Mockery::mock(Connection::class);

        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->setCurrentJobProcessingEvent($listener, null);

        $this->stickiness->shouldNotReceive('initializeStickinessState');
        $this->stickiness->shouldReceive('resolveRecordsModified')->once();

        $listener->onConnectionCreated(new ConnectionCreated($connection));
    }

    public function testOnRecordsHaveBeenModified(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->stickiness->shouldReceive('markAsModified')->once()->with($connection);
        $connection->shouldReceive('getName')->andReturn('foo');

        $listener = new StickinessEventListener($this->stickiness, $this->db);

        $this->setCurrentRecordsModifiedStates($listener, ['foo' => false, 'bar' => false]);

        $listener->onRecordsHaveBeenModified(new RecordsHaveBeenModified($connection));

        $this->assertSame(['bar' => false], $this->getCurrentRecordsModifiedStates($listener));
    }
}
