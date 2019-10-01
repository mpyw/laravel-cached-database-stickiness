<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\Concerns\RetrievesTTL;

/**
 * Class IpBasedResolver
 *
 * Guarantee stickiness for each remote IP address.
 */
class IpBasedResolver implements StickinessResolverInterface
{
    use RetrievesTTL;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * IpBasedResolver constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Illuminate\Http\Request               $request
     */
    public function __construct(CacheRepository $cache, Request $request)
    {
        $this->cache = $cache;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsModified(ConnectionInterface $connection): void
    {
        if ($ip = $this->request->ip()) {
            $this->cache->put(static::getCacheKey($connection, $ip), true, $this->ttl($connection));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isRecentlyModified(ConnectionInterface $connection): bool
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        return ($ip = $this->request->ip())
            ? $this->cache->has(static::getCacheKey($connection, $ip))
            : false;
    }

    /**
     * @param  \Illuminate\Database\Connection|\Illuminate\Database\ConnectionInterface $connection
     * @param  string                                                                   $ip
     * @return string
     */
    protected static function getCacheKey(ConnectionInterface $connection, string $ip): string
    {
        return sprintf(
            'database-stickiness:connection=%s,resolver=ip,ip=%s',
            $connection->getName(),
            $ip
        );
    }
}
