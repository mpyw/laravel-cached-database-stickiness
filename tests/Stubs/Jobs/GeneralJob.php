<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class GeneralJob implements ShouldQueue
{
    public function handle(): void
    {
    }
}
