<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Http\Middleware;

use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessManager;

class ResolveStickinessOnResolvedConnections
{
    /**
     * @var \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager
     */
    protected $stickiness;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * ResolveStickinessOnResolvedConnections constructor.
     *
     * @param \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager $stickiness
     * @param \Illuminate\Database\DatabaseManager                    $db
     */
    public function __construct(StickinessManager $stickiness, DatabaseManager $db)
    {
        $this->stickiness = $stickiness;
        $this->db = $db;
    }

    /**
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        foreach ($this->db->getConnections() as $connection) {
            $this->stickiness->resolveRecordsModified($connection);
        }

        return $next($request);
    }
}
