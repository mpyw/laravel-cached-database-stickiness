<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature;

use Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeModified;

class ModifiedJob implements ShouldAssumeModified
{
    public function handle(): void
    {
    }
}
