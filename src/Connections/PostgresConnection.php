<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Connections;

use Illuminate\Database\PostgresConnection as BasePostgresConnection;
use Mpyw\LaravelCachedDatabaseStickiness\DispatchesConnectionEvents;

class PostgresConnection extends BasePostgresConnection
{
    use DispatchesConnectionEvents;
}
