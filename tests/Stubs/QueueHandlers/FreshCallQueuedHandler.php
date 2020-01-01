<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\QueueHandlers;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshCallQueuedHandler implements ShouldAssumeFresh
{
    public function call(): void
    {
    }
}
