<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests;

use Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeFresh;

class FreshJob implements ShouldAssumeFresh
{
    public function handle(): void
    {
    }
}
