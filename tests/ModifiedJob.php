<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests;

use Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeModified;

class ModifiedJob implements ShouldAssumeModified
{
    public function handle(): void
    {
    }
}
