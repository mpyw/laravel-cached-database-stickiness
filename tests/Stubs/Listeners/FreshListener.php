<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshListener implements ShouldAssumeFresh
{
    public function handle(): void
    {
    }
}
