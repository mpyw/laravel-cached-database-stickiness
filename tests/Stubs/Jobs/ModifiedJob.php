<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedJob implements ShouldQueue, ShouldAssumeModified
{
    public function handle(): void
    {
    }
}
