<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\ApplyingJobInitialization;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface;
use Orchestra\Testbench\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class StickinessManagerTest extends TestCase
{
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface
     */
    protected $resolver;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface
     */
    protected $job;

    /**
     * @var \Illuminate\Contracts\Container\Container|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $container;

    /**
     * @var \Illuminate\Database\DatabaseManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = Mockery::mock(StickinessResolverInterface::class);
        $this->job = Mockery::mock(JobInitializerInterface::class);
        $this->container = Mockery::mock(Container::class);
        $this->db = Mockery::mock(DatabaseManager::class);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveRecordsModified(): void
    {
        $this->container->shouldReceive('make')
            ->once()
            ->with(StickinessResolverInterface::class)
            ->andReturn($this->resolver);

        $connection = (new ReflectionClass(Connection::class))->newInstanceWithoutConstructor();

        $this->resolver->shouldReceive('isRecentlyModified')->once()->with($connection)->andReturnTrue();

        $this->assertFalse($connection->hasModifiedRecords());

        $manager = new StickinessManager($this->container, $this->db);
        $manager->resolveRecordsModified($connection);

        $this->assertTrue($connection->hasModifiedRecords());
    }

    public function testResolveRecordsAlreadyModified(): void
    {
        $connection = (new ReflectionClass(Connection::class))->newInstanceWithoutConstructor();

        $this->resolver->shouldNotReceive('isRecentlyModified');

        $connection->setRecordModificationState(true);

        $manager = new StickinessManager($this->container, $this->db);
        $manager->resolveRecordsModified($connection);

        $this->assertTrue($connection->hasModifiedRecords());
    }

    public function testMarkAsModified(): void
    {
        $this->container->shouldReceive('make')
            ->once()
            ->with(StickinessResolverInterface::class)
            ->andReturn($this->resolver);

        $connection = Mockery::mock(Connection::class);

        $this->resolver->shouldReceive('markAsModified')->once();

        $manager = new StickinessManager($this->container, $this->db);
        $manager->markAsModified($connection);
    }

    public function testIsRecentlyModified(): void
    {
        $this->container->shouldReceive('make')
            ->once()
            ->with(StickinessResolverInterface::class)
            ->andReturn($this->resolver);

        $connection = Mockery::mock(Connection::class);

        $this->resolver->shouldReceive('isRecentlyModified')->once()->andReturnTrue();

        $manager = new StickinessManager($this->container, $this->db);
        $this->assertTrue($manager->isRecentlyModified($connection));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStartInitializingJob(): void
    {
        $this->container->shouldReceive('make')
            ->once()
            ->with(JobInitializerInterface::class)
            ->andReturn($this->job);

        $initialization = Mockery::mock('overload:' . ApplyingJobInitialization::class);
        $initialization->shouldReceive('initializeOnResolvedConnections')->once()->andReturnSelf();

        $event = Mockery::mock(JobProcessing::class);

        $manager = new StickinessManager($this->container, $this->db);
        $this->assertInstanceOf(ApplyingJobInitialization::class, $manager->startInitializingJob($event));
    }
}
