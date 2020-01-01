<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshJob implements ShouldQueue, ShouldAssumeFresh
{
    public function handle(): void
    {
    }
}
