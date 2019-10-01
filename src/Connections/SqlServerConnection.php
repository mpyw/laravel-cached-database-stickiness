<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Connections;

use Illuminate\Database\SqlServerConnection as BaseSqlServerConnection;
use Mpyw\LaravelCachedDatabaseStickiness\DispatchesConnectionEvents;

class SqlServerConnection extends BaseSqlServerConnection
{
    use DispatchesConnectionEvents;
}
