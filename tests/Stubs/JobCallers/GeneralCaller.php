<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers;

use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class GeneralCaller
{
    use LogsConnectionState;

    public function call(): void
    {
        $this->logState();
    }
}
