<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications;

use Illuminate\Notifications\Notification;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshNotification extends Notification implements ShouldAssumeFresh
{
}
