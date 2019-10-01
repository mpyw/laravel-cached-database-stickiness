<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\StickinessResolvers;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\IpBasedResolver;
use Orchestra\Testbench\TestCase;

class IpBasedResolverTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $cache;

    /**
     * @var \Illuminate\Http\Request|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $request;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = Mockery::mock(Repository::class);
        $this->request = Mockery::mock(Request::class);
        $this->connection = Mockery::mock(ConnectionInterface::class);
    }

    public function testMarkAsModified(): void
    {
        $this->request->shouldReceive('ip')->once()->andReturn('192.168.0.1');
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->connection->shouldReceive('getConfig')->once()->with('stickiness_ttl')->andReturnNull();
        $this->cache->shouldReceive('put')->once()->with(
            'database-stickiness:connection=foo,resolver=ip,ip=192.168.0.1',
            true,
            5
        );

        $resolver = new IpBasedResolver($this->cache, $this->request);
        $resolver->markAsModified($this->connection);
    }

    public function testMarkAsModifiedUsingCustomTTL(): void
    {
        $this->request->shouldReceive('ip')->once()->andReturn('192.168.0.1');
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->connection->shouldReceive('getConfig')->once()->with('stickiness_ttl')->andReturn(2);
        $this->cache->shouldReceive('put')->once()->with(
            'database-stickiness:connection=foo,resolver=ip,ip=192.168.0.1',
            true,
            2
        );

        $resolver = new IpBasedResolver($this->cache, $this->request);
        $resolver->markAsModified($this->connection);
    }

    public function testDontMarkAsModifiedWithoutIp(): void
    {
        $this->request->shouldReceive('ip')->once()->andReturnNull();
        $this->connection->shouldNotReceive('getName');
        $this->connection->shouldNotReceive('getConfig');
        $this->cache->shouldNotReceive('put');

        $resolver = new IpBasedResolver($this->cache, $this->request);
        $resolver->markAsModified($this->connection);
    }

    public function testIsRecentlyModifiedWhenCacheExists(): void
    {
        $this->request->shouldReceive('ip')->once()->andReturn('192.168.0.1');
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->cache->shouldReceive('has')->once()->with('database-stickiness:connection=foo,resolver=ip,ip=192.168.0.1')->andReturnTrue();

        $resolver = new IpBasedResolver($this->cache, $this->request);
        $this->assertTrue($resolver->isRecentlyModified($this->connection));
    }

    public function testIsRecentlyModifiedWhenCacheDoesNotExist(): void
    {
        $this->request->shouldReceive('ip')->once()->andReturn('192.168.0.1');
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->cache->shouldReceive('has')->once()->with('database-stickiness:connection=foo,resolver=ip,ip=192.168.0.1')->andReturnFalse();

        $resolver = new IpBasedResolver($this->cache, $this->request);
        $this->assertFalse($resolver->isRecentlyModified($this->connection));
    }

    public function testIsRecentlyModifiedWithoutIp(): void
    {
        $this->request->shouldReceive('ip')->once()->andReturnNull();
        $this->connection->shouldNotReceive('getName');
        $this->cache->shouldNotReceive('has');

        $resolver = new IpBasedResolver($this->cache, $this->request);
        $this->assertFalse($resolver->isRecentlyModified($this->connection));
    }
}
