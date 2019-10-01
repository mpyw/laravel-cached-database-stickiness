<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\Concerns\RetrievesTTL;
use ReflectionProperty;

/**
 * Class AuthBasedResolver
 *
 * Guarantee stickiness for each logged-in user.
 */
class AuthBasedResolver implements StickinessResolverInterface
{
    use RetrievesTTL;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected $guard;

    /**
     * AuthBasedResolver constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Illuminate\Contracts\Auth\Guard       $guard
     */
    public function __construct(CacheRepository $cache, Guard $guard)
    {
        $this->cache = $cache;
        $this->guard = $guard;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsModified(ConnectionInterface $connection): void
    {
        if ($this->hasUser()) {
            $this->cache->put(static::getCacheKey($connection, $this->guard->user()), true, $this->ttl($connection));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isRecentlyModified(ConnectionInterface $connection): bool
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        return $this->hasUser()
            ? $this->cache->has(static::getCacheKey($connection, $this->guard->user()))
            : false;
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @return bool
     */
    protected function hasUser(): bool
    {
        if (method_exists($this->guard, 'hasUser')) {
            return $this->guard->hasUser();
        }

        if (!property_exists($this->guard, 'user')) {
            return false;
        }

        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($this->guard, 'user');
        $property->setAccessible(true);
        return (bool)$property->getValue($this->guard);
    }

    /**
     * @param  \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     * @param  \Illuminate\Contracts\Auth\Authenticatable                               $user
     * @return string
     */
    protected static function getCacheKey(ConnectionInterface $connection, Authenticatable $user): string
    {
        return sprintf(
            'database-stickiness:connection=%s,resolver=auth,%s=%s',
            $connection->getName(),
            $user->getAuthIdentifierName(),
            $user->getAuthIdentifier()
        );
    }
}
