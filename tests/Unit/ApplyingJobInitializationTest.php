<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\ApplyingJobInitialization;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class ApplyingJobInitializationTest extends TestCase
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
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface
     */
    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stickiness = Mockery::mock(StickinessManager::class);
        $this->db = Mockery::mock(DatabaseManager::class);
        $this->job = Mockery::mock(JobInitializerInterface::class);
    }

    protected function getRecordsModifiedStates(ApplyingJobInitialization $initialization): array
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($initialization, 'recordsModifiedStates');
        $property->setAccessible(true);
        return $property->getValue($initialization);
    }

    protected function setRecordsModifiedStates(ApplyingJobInitialization $initialization, array $states): void
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($initialization, 'recordsModifiedStates');
        $property->setAccessible(true);
        $property->setValue($initialization, $states);
    }

    /**
     * @param  array                                                                                                               $args
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\ApplyingJobInitialization
     */
    protected function newApplyingJobInitializationWithoutDestructor(...$args): ApplyingJobInitialization
    {
        $initialization = Mockery::mock(ApplyingJobInitialization::class . '[__destruct]', $args);
        $initialization->shouldReceive('__destruct')->zeroOrMoreTimes();
        return $initialization;
    }

    public function testInitializeOnResolvedConnections(): void
    {
        $event = Mockery::mock(JobProcessing::class);
        $connection = Mockery::mock(Connection::class);

        $this->db->shouldReceive('getConnections')->once()->andReturn([$connection]);
        $connection->shouldReceive('getName')->once()->andReturn('bar');
        $this->stickiness->shouldReceive('getRecordsModified')->once()->with($connection)->andReturnTrue();
        $this->job->shouldReceive('initializeOnResolvedConnections')->once()->with($event);

        $initialization = $this->newApplyingJobInitializationWithoutDestructor($this->stickiness, $this->db, $this->job);

        $this->setRecordsModifiedStates($initialization, ['foo' => true]);

        $this->assertSame($initialization, $initialization->initializeOnResolvedConnections($event));
        $this->assertSame(['foo' => true, 'bar' => true], $this->getRecordsModifiedStates($initialization));
    }

    public function testInitializeOnNewConnection(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connectionCreatedEvent = new ConnectionCreated($connection);
        $jobProcessingEvent = Mockery::mock(JobProcessing::class);

        $connection->shouldReceive('getName')->once()->andReturn('bar');
        $this->stickiness->shouldReceive('getRecordsModified')->once()->with($connection)->andReturnTrue();
        $this->job->shouldReceive('initializeOnNewConnection')->once()->with($jobProcessingEvent, $connectionCreatedEvent);

        $initialization = $this->newApplyingJobInitializationWithoutDestructor($this->stickiness, $this->db, $this->job);

        $this->setRecordsModifiedStates($initialization, ['foo' => true]);

        $this->assertSame($initialization, $initialization->initializeOnNewConnection($jobProcessingEvent, $connectionCreatedEvent));
        $this->assertSame(['foo' => true, 'bar' => true], $this->getRecordsModifiedStates($initialization));
    }

    public function testDontRevokeEffectsOn(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getName')->once()->andReturn('bar');

        $initialization = $this->newApplyingJobInitializationWithoutDestructor($this->stickiness, $this->db, $this->job);

        $this->setRecordsModifiedStates($initialization, ['foo' => true, 'bar' => true]);

        $this->assertSame($initialization, $initialization->dontRevokeEffectsOn($connection));
        $this->assertSame(['foo' => true], $this->getRecordsModifiedStates($initialization));
    }

    public function testDestructor(): void
    {
        $foo = Mockery::mock(Connection::class);
        $foo->shouldReceive('getName')->once()->andReturn('foo');
        $this->stickiness->shouldReceive('setRecordsModified')->with($foo, true)->once();

        $bar = Mockery::mock(Connection::class);
        $bar->shouldReceive('getName')->once()->andReturn('bar');
        $this->stickiness->shouldReceive('setRecordsModified')->with($bar, false)->once();

        $baz = Mockery::mock(Connection::class);
        $baz->shouldReceive('getName')->once()->andReturn('baz');
        $this->stickiness->shouldNotReceive('setRecordsModified')->with($baz, null);

        $this->db->shouldReceive('getConnections')->once()->andReturn([$foo, $bar, $baz]);

        $initialization = new ApplyingJobInitialization($this->stickiness, $this->db, $this->job);

        $this->setRecordsModifiedStates($initialization, ['foo' => true, 'bar' => false, 'qux' => true, 'quux' => false]);

        unset($initialization);
    }
}
