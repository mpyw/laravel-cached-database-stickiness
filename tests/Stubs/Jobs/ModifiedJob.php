<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class ModifiedJob implements ShouldQueue, ShouldAssumeModified
{
    use LogsConnectionState;

    public function handle(): void
    {
        $this->logState();
    }
}
