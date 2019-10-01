<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Connections;

use Illuminate\Database\SQLiteConnection as BaseSQLiteConnection;
use Mpyw\LaravelCachedDatabaseStickiness\DispatchesConnectionEvents;

class SQLiteConnection extends BaseSQLiteConnection
{
    use DispatchesConnectionEvents;
}
