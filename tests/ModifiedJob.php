<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedJob implements ShouldAssumeModified
{
    public function handle(): void
    {
    }
}
