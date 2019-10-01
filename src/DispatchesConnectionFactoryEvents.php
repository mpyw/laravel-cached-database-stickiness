<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Contracts\Events\Dispatcher;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;

/**
 * Class DispatchesConnectionFactoryEvents
 *
 * @mixin \Illuminate\Database\Connectors\ConnectionFactory
 */
trait DispatchesConnectionFactoryEvents
{
    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * Dispatch RecordsHaveBeenModified event when records newly modified.
     *
     * @param  array                           $config
     * @param  null|string                     $name
     * @return \Illuminate\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        $connection = parent::make($config, $name);

        /* @noinspection PhpUnhandledExceptionInspection */
        $this->container->make(Dispatcher::class)->dispatch(new ConnectionCreated($connection));

        return $connection;
    }
}
