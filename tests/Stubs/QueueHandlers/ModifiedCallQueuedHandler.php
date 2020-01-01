<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\QueueHandlers;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedCallQueuedHandler implements ShouldAssumeModified
{
    public function call(): void
    {
    }
}
