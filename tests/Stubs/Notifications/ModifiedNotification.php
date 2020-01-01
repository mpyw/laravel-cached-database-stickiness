<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications;

use Illuminate\Notifications\Notification;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedNotification extends Notification implements ShouldAssumeModified
{
}
