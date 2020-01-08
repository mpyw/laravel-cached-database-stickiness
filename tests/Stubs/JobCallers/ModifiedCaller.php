<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class ModifiedCaller implements ShouldAssumeModified
{
    use LogsConnectionState;

    public function call(): void
    {
        $this->logState();
    }
}
