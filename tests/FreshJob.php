<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshJob implements ShouldAssumeFresh
{
    public function handle(): void
    {
    }
}
