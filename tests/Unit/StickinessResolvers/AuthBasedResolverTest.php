<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\StickinessResolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\AuthBasedResolver;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class AuthBasedResolverTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $cache;

    /**
     * @var \Illuminate\Contracts\Auth\Guard|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $guard;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $connection;

    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = Mockery::mock(Repository::class);
        $this->guard = Mockery::mock(Guard::class);
        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->user = Mockery::mock(Authenticatable::class);
    }

    protected function getUserViaReflection(Guard $guard): bool
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($guard, 'user');
        $property->setAccessible(true);
        return $property->getValue($guard);
    }

    protected function setUserViaReflection(Guard $guard, ?Authenticatable $user): void
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($guard, 'user');
        $property->setAccessible(true);
        $property->setValue($guard, $user);
    }

    public function testMarkAsModified(): void
    {
        $this->guard->shouldReceive('hasUser')->once()->andReturnTrue();
        $this->guard->shouldReceive('user')->once()->andReturn($this->user);
        $this->user->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $this->user->shouldReceive('getAuthIdentifier')->once()->andReturn(1);
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->connection->shouldReceive('getConfig')->once()->with('stickiness_ttl')->andReturnNull();
        $this->cache->shouldReceive('put')->once()->with(
            'database-stickiness:connection=foo,resolver=auth,id=1',
            true,
            5
        );

        $resolver = new AuthBasedResolver($this->cache, $this->guard);
        $resolver->markAsModified($this->connection);
    }

    public function testMarkAsModifiedUsingCustomTTL(): void
    {
        $this->guard->shouldReceive('hasUser')->once()->andReturnTrue();
        $this->guard->shouldReceive('user')->once()->andReturn($this->user);
        $this->user->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $this->user->shouldReceive('getAuthIdentifier')->once()->andReturn(1);
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->connection->shouldReceive('getConfig')->once()->with('stickiness_ttl')->andReturn(2);
        $this->cache->shouldReceive('put')->once()->with(
            'database-stickiness:connection=foo,resolver=auth,id=1',
            true,
            2
        );

        $resolver = new AuthBasedResolver($this->cache, $this->guard);
        $resolver->markAsModified($this->connection);
    }

    public function testDontMarkAsModifiedWithoutUser(): void
    {
        $this->guard->shouldReceive('hasUser')->once()->andReturnFalse();
        $this->guard->shouldNotReceive('user');
        $this->user->shouldNotReceive('getAuthIdentifierName');
        $this->user->shouldNotReceive('getAuthIdentifier');
        $this->connection->shouldNotReceive('getName');
        $this->connection->shouldNotReceive('getConfig');
        $this->cache->shouldNotReceive('put');

        $resolver = new AuthBasedResolver($this->cache, $this->guard);
        $resolver->markAsModified($this->connection);
    }

    public function testIsRecentlyModifiedWhenCacheExists(): void
    {
        $this->guard->shouldReceive('hasUser')->once()->andReturnTrue();
        $this->guard->shouldReceive('user')->once()->andReturn($this->user);
        $this->user->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $this->user->shouldReceive('getAuthIdentifier')->once()->andReturn(1);
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->cache->shouldReceive('has')->once()->with('database-stickiness:connection=foo,resolver=auth,id=1')->andReturnTrue();

        $resolver = new AuthBasedResolver($this->cache, $this->guard);
        $this->assertTrue($resolver->isRecentlyModified($this->connection));
    }

    public function testIsRecentlyModifiedWhenCacheDoesNotExist(): void
    {
        $this->guard->shouldReceive('hasUser')->once()->andReturnTrue();
        $this->guard->shouldReceive('user')->once()->andReturn($this->user);
        $this->user->shouldReceive('getAuthIdentifierName')->once()->andReturn('id');
        $this->user->shouldReceive('getAuthIdentifier')->once()->andReturn(1);
        $this->connection->shouldReceive('getName')->once()->andReturn('foo');
        $this->cache->shouldReceive('has')->once()->with('database-stickiness:connection=foo,resolver=auth,id=1')->andReturnFalse();

        $resolver = new AuthBasedResolver($this->cache, $this->guard);
        $this->assertFalse($resolver->isRecentlyModified($this->connection));
    }

    public function testIsRecentlyModifiedWithoutUser(): void
    {
        $this->guard->shouldReceive('hasUser')->once()->andReturnFalse();
        $this->guard->shouldNotReceive('user');
        $this->user->shouldNotReceive('getAuthIdentifierName');
        $this->user->shouldNotReceive('getAuthIdentifier');
        $this->connection->shouldNotReceive('getName');
        $this->cache->shouldNotReceive('has');

        $resolver = new AuthBasedResolver($this->cache, $this->guard);
        $this->assertFalse($resolver->isRecentlyModified($this->connection));
    }
}
