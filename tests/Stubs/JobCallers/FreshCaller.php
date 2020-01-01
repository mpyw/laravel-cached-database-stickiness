<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshCaller implements ShouldAssumeFresh
{
    public function call(): void
    {
    }
}
