<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Mail\Mailable;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshMailable extends Mailable implements ShouldAssumeFresh
{
}
