<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class FreshListener implements ShouldQueue, ShouldAssumeFresh
{
    use LogsConnectionState;

    public function handle(): void
    {
        $this->logState();
    }
}
