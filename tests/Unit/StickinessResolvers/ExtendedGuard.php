<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Unit\StickinessResolvers;

use Illuminate\Contracts\Auth\Guard;

interface ExtendedGuard extends Guard
{
    /**
     * Determine if the guard has a user instance.
     *
     * @return bool
     */
    public function hasUser();
}
