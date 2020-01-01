<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Mail\Mailable;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedMailable extends Mailable implements ShouldAssumeModified
{
}
