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
use Mpyw\LaravelCachedDatabaseStickiness\ApplyingJobInitialization;
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
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\ApplyingJobInitialization
     */
    protected $initialization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stickiness = Mockery::mock(StickinessManager::class);
        $this->initialization = Mockery::mock(ApplyingJobInitialization::class);
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

    protected function setCurrentJobInitialization(StickinessEventListener $listener, ?ApplyingJobInitialization $event): void
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($listener, 'currentJobInitialization');
        $property->setAccessible(true);
        $property->setValue($listener, $event);
    }

    protected function getCurrentJobInitialization(StickinessEventListener $listener): ?ApplyingJobInitialization
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($listener, 'currentJobInitialization');
        $property->setAccessible(true);
        return $property->getValue($listener);
    }

    public function testOnJobProcessing(): void
    {
        $event = Mockery::mock(JobProcessing::class);

        $this->stickiness->shouldReceive('startInitializingJob')->once()->andReturn($this->initialization);

        $listener = new StickinessEventListener($this->stickiness);

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertNull($this->getCurrentJobInitialization($listener));

        $listener->onJobProcessing($event);

        $this->assertSame($event, $this->getCurrentJobProcessingEvent($listener));
        $this->assertSame($this->initialization, $this->getCurrentJobInitialization($listener));
    }

    public function testOnJobProcessed(): void
    {
        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, Mockery::mock(JobProcessing::class));
        $this->setCurrentJobInitialization($listener, $this->initialization);

        $listener->onJobProcessed(Mockery::mock(JobProcessed::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertNull($this->getCurrentJobInitialization($listener));
    }

    public function testOnJobExceptionOccurred(): void
    {
        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, Mockery::mock(JobProcessing::class));
        $this->setCurrentJobInitialization($listener, $this->initialization);

        $listener->onJobExceptionOccurred(Mockery::mock(JobExceptionOccurred::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertNull($this->getCurrentJobInitialization($listener));
    }

    public function testOnJobFailed(): void
    {
        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, Mockery::mock(JobProcessing::class));
        $this->setCurrentJobInitialization($listener, $this->initialization);

        $listener->onJobFailed(Mockery::mock(JobFailed::class));

        $this->assertNull($this->getCurrentJobProcessingEvent($listener));
        $this->assertNull($this->getCurrentJobInitialization($listener));
    }

    public function onConnectionCreatedWithCurrentJob(): void
    {
        $job = Mockery::mock(Job::class);
        $connection = Mockery::mock(Connection::class);

        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, $event = new JobProcessing('foo', $job));
        $this->setCurrentJobInitialization($listener, $this->initialization);

        $this->initialization->shouldReceive('initializeOnNewConnection')->once()->with($event)->andReturnSelf();
        $this->stickiness->shouldReceive('resolveRecordsModified')->once();

        $listener->onConnectionCreated(new ConnectionCreated($connection));
    }

    public function onConnectionCreatedWithoutCurrentJob(): void
    {
        $connection = Mockery::mock(Connection::class);

        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, null);
        $this->setCurrentJobInitialization($listener, null);

        $this->initialization->shouldNotReceive('initializeOnNewConnection');
        $this->stickiness->shouldReceive('resolveRecordsModified')->once();

        $listener->onConnectionCreated(new ConnectionCreated($connection));
    }

    public function testOnRecordsHaveBeenModifiedWithCurrentJob(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, Mockery::mock(JobProcessing::class));
        $this->setCurrentJobInitialization($listener, $this->initialization);

        $this->initialization->shouldReceive('dontRevokeEffectsOn')->once()->with($connection)->andReturnSelf();
        $this->stickiness->shouldReceive('markAsModified')->once()->with($connection);

        $listener->onRecordsHaveBeenModified(new RecordsHaveBeenModified($connection));
    }

    public function testOnRecordsHaveBeenModifiedWithoutCurrentJob(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        $listener = new StickinessEventListener($this->stickiness);

        $this->setCurrentJobProcessingEvent($listener, null);
        $this->setCurrentJobInitialization($listener, null);

        $this->initialization->shouldNotReceive('dontRevokeEffectsOn');
        $this->stickiness->shouldReceive('markAsModified')->once()->with($connection);

        $listener->onRecordsHaveBeenModified(new RecordsHaveBeenModified($connection));
    }
}
