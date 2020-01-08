<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class FreshCaller implements ShouldAssumeFresh
{
    use LogsConnectionState;

    public function call(): void
    {
        $this->logState();
    }
}
