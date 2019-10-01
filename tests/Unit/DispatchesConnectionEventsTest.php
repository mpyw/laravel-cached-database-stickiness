<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit;

use Hamcrest\Core\IsInstanceOf;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified;
use Orchestra\Testbench\TestCase;

class DispatchesConnectionEventsTest extends TestCase
{
    public function testDispatch(): void
    {
        /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\DispatchableConnection $connection */
        $connection = Mockery::mock(DispatchableConnection::class . '[getEventDispatcher]');
        $dispatcher = Mockery::mock(Dispatcher::class);

        $connection->shouldReceive('getEventDispatcher')->once()->andReturn($dispatcher);
        $dispatcher->shouldReceive('dispatch')->once()->with(IsInstanceOf::anInstanceOf(RecordsHaveBeenModified::class));

        $connection->recordsHaveBeenModified();
        $connection->recordsHaveBeenModified();

        $this->assertTrue($connection->recordsModified);
    }

    public function testDontDispatchWhenMissingDispatcher(): void
    {
        /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\DispatchableConnection $connection */
        $connection = Mockery::mock(DispatchableConnection::class . '[getEventDispatcher]');
        $dispatcher = Mockery::mock(Dispatcher::class);

        $connection->shouldReceive('getEventDispatcher')->once()->andReturn(null);
        $dispatcher->shouldNotReceive('dispatch');

        $connection->recordsHaveBeenModified();
        $connection->recordsHaveBeenModified();

        $this->assertTrue($connection->recordsModified);
    }

    public function testDontDispatchWhenNotModified(): void
    {
        /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\DispatchableConnection $connection */
        $connection = Mockery::mock(DispatchableConnection::class . '[getEventDispatcher]');
        $dispatcher = Mockery::mock(Dispatcher::class);

        $connection->shouldNotReceive('getEventDispatcher');
        $dispatcher->shouldNotReceive('dispatch');

        $connection->recordsHaveBeenModified(false);
        $connection->recordsHaveBeenModified(false);

        $this->assertFalse($connection->recordsModified);
    }

    public function testDontBeFreshAfterModified(): void
    {
        /** @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\DispatchableConnection $connection */
        $connection = Mockery::mock(DispatchableConnection::class . '[getEventDispatcher]');
        $dispatcher = Mockery::mock(Dispatcher::class);

        $connection->shouldReceive('getEventDispatcher')->once()->andReturn($dispatcher);
        $dispatcher->shouldReceive('dispatch')->once()->with(IsInstanceOf::anInstanceOf(RecordsHaveBeenModified::class));

        $connection->recordsHaveBeenModified();

        $this->assertTrue($connection->recordsModified);

        $connection->recordsHaveBeenModified(false);

        $this->assertTrue($connection->recordsModified);
    }
}
