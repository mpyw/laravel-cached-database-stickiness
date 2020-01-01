<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedListener implements ShouldAssumeModified
{
    public function handle(): void
    {
    }
}
