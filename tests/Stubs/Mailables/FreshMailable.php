<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;

class FreshMailable extends Mailable implements ShouldQueue, ShouldAssumeFresh
{
    public function build()
    {
        return $this->to('example@example.com')->subject('example')->html('example');
    }
}
