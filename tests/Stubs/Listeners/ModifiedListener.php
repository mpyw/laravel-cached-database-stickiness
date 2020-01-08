<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class ModifiedListener implements ShouldQueue, ShouldAssumeModified
{
    use LogsConnectionState;

    public function handle(): void
    {
        $this->logState();
    }
}
