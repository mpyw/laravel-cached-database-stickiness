<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers;

use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedCaller implements ShouldAssumeModified
{
    public function call(): void
    {
    }
}
