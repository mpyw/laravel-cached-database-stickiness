<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature;

use Mpyw\LaravelCachedDatabaseStickiness\Jobs\ShouldAssumeFresh;

class FreshJob implements ShouldAssumeFresh
{
    public function handle(): void
    {
    }
}
