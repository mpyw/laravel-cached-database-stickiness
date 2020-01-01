<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface;
use Orchestra\Testbench\TestCase;
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
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = Mockery::mock(StickinessResolverInterface::class);
        $this->job = Mockery::mock(JobInitializerInterface::class);
        $this->container = Mockery::mock(Container::class);

        $this->container->shouldReceive('make')
            ->zeroOrMoreTimes()
            ->with(JobInitializerInterface::class)
            ->andReturn($this->job);
        $this->container->shouldReceive('make')
            ->zeroOrMoreTimes()
            ->with(StickinessResolverInterface::class)
            ->andReturn($this->resolver);
    }

    protected function getRecordsModified(ConnectionInterface $connection): bool
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'recordsModified');
        $property->setAccessible(true);
        return $property->getValue($connection);
    }

    protected function setRecordsModified(ConnectionInterface $connection, bool $bool): void
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'recordsModified');
        $property->setAccessible(true);
        $property->setValue($connection, $bool);
    }

    public function testOnJobProcessing(): void
    {
        $event = Mockery::mock(JobProcessing::class);
        $this->job->shouldReceive('initializeStickinessState')->once()->with($event);

        $manager = new StickinessManager($this->container);
        $manager->onJobProcessing($event);
    }

    public function testOnRecordsHaveBeenModified(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $this->resolver->shouldReceive('markAsModified')->once()->with($connection);

        $manager = new StickinessManager($this->container);
        $manager->onRecordsHaveBeenModified(new RecordsHaveBeenModified($connection));
    }

    public function testSetRecordsModified(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->assertFalse($this->getRecordsModified($connection));

        $manager = new StickinessManager($this->container);
        $manager->setRecordsModified($connection);

        $this->assertTrue($this->getRecordsModified($connection));
    }

    public function testGetRecordsModified(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->setRecordsModified($connection, true);

        $manager = new StickinessManager($this->container);
        $this->assertTrue($manager->getRecordsModified($connection));
    }

    public function testSetRecordsFresh(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->setRecordsModified($connection, true);

        $manager = new StickinessManager($this->container);
        $manager->setRecordsFresh($connection);

        $this->assertFalse($this->getRecordsModified($connection));
    }

    public function testResolveRecordsModified(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->resolver->shouldReceive('isRecentlyModified')->once()->with($connection)->andReturnTrue();

        $this->assertFalse($this->getRecordsModified($connection));

        $manager = new StickinessManager($this->container);
        $manager->resolveRecordsModified($connection);

        $this->assertTrue($this->getRecordsModified($connection));
    }

    public function testResolveRecordsNotModified(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->resolver->shouldNotReceive('isRecentlyModified');

        $this->setRecordsModified($connection, true);

        $manager = new StickinessManager($this->container);
        $manager->resolveRecordsModified($connection);

        $this->assertTrue($this->getRecordsModified($connection));
    }

    public function testMarkAsModified(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->resolver->shouldReceive('markAsModified')->once();

        $manager = new StickinessManager($this->container);
        $manager->markAsModified($connection);
    }

    public function testIsRecentlyModified(): void
    {
        $connection = Mockery::mock(Connection::class);

        $this->resolver->shouldReceive('isRecentlyModified')->once()->andReturnTrue();

        $manager = new StickinessManager($this->container);
        $this->assertTrue($manager->isRecentlyModified($connection));
    }

    public function testInitializeStickinessState(): void
    {
        $event = Mockery::mock(JobProcessing::class);
        $this->job->shouldReceive('initializeStickinessState')->once()->with($event);

        $manager = new StickinessManager($this->container);
        $manager->initializeStickinessState($event);
    }
}
