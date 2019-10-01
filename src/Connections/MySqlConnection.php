<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Connections;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;
use Mpyw\LaravelCachedDatabaseStickiness\DispatchesConnectionEvents;

class MySqlConnection extends BaseMySqlConnection
{
    use DispatchesConnectionEvents;
}
