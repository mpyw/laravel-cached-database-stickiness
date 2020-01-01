<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class ConnectionResolvableJob implements ShouldQueue
{
    public function handle(): void
    {
        DB::connection();
    }
}
