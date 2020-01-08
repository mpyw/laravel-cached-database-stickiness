<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class GeneralListener implements ShouldQueue
{
    use LogsConnectionState;

    public function handle(): void
    {
        $this->logState();
    }
}
