<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class SideEffectFreshJob implements ShouldQueue, ShouldAssumeFresh
{
    use LogsConnectionState;

    public function handle(): void
    {
        DB::recordsHaveBeenModified();

        $this->logState();
    }
}
