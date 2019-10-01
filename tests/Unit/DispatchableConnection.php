<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit;

use Illuminate\Database\Connection;
use Mpyw\LaravelCachedDatabaseStickiness\DispatchesConnectionEvents;

class DispatchableConnection extends Connection
{
    use DispatchesConnectionEvents;

    public $recordsModified;

    public function __construct()
    {
    }
}
