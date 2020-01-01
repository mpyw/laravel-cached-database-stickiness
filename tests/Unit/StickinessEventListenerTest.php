<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->stickiness = Mockery::mock(StickinessManager::class);
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

    public function testOnJobProcessing(): void
    {
        $event = Mockery::mock(JobProcessing::class);

        $this->stickiness->shouldReceive('initializeStickinessState')->once()->with($event);

        $listener = new StickinessEventListener($this->stickiness);

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));

        $listener->onJobProcessing($event);

        $this->assertSame($event, $this->getCurrentJobProcessingEvent($listener));
    }

    public function testOnJobProcessed(): void
    {
        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, $event = Mockery::mock(JobProcessing::class));

        $listener->onJobProcessed(Mockery::mock(JobProcessed::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
    }

    public function testOnJobExceptionOccurred(): void
    {
        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, $event = Mockery::mock(JobProcessing::class));

        $listener->onJobExceptionOccurred(Mockery::mock(JobExceptionOccurred::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
    }

    public function testOnJobFailed(): void
    {
        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, $event = Mockery::mock(JobProcessing::class));

        $listener->onJobFailed(Mockery::mock(JobFailed::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
    }

    public function onConnectionCreatedWithCurrentJobProcessingEvent(): void
    {
        $job = Mockery::mock(Job::class);
        $connection = Mockery::mock(Connection::class);

        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, $event = new JobProcessing('foo', $job));

        $this->stickiness->shouldReceive('initializeStickinessState')->once()->with($event);
        $this->stickiness->shouldReceive('resolveRecordsModified')->once();

        $listener->onConnectionCreated(new ConnectionCreated($connection));
    }

    public function onConnectionCreatedWithoutCurrentJobProcessingEvent(): void
    {
        $connection = Mockery::mock(Connection::class);

        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, null);

        $this->stickiness->shouldNotReceive('initializeStickinessState');
        $this->stickiness->shouldReceive('resolveRecordsModified')->once();

        $listener->onConnectionCreated(new ConnectionCreated($connection));
    }

    public function testOnRecordsHaveBeenModified(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        $this->stickiness->shouldReceive('markAsModified')->once()->with($connection);

        $listener = new StickinessEventListener($this->stickiness);
        $listener->onRecordsHaveBeenModified(new RecordsHaveBeenModified($connection));
    }
}
