<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshJob implements ShouldAssumeFresh
{
    public function handle(): void
    {
    }
}
