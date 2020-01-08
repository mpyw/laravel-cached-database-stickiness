<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class GeneralJob implements ShouldQueue
{
    use LogsConnectionState;

    public function handle(): void
    {
        $this->logState();
    }
}
