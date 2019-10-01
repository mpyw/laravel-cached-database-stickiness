<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;

/**
 * Class ConnectionFactory
 */
class ConnectionFactory extends BaseConnectionFactory
{
    use DispatchesConnectionFactoryEvents;
}
