<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedJob implements ShouldAssumeModified
{
    public function handle(): void
    {
    }
}
