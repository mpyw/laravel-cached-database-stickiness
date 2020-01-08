<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class FreshMailable extends Mailable implements ShouldQueue, ShouldAssumeFresh
{
    use LogsConnectionState;

    public function build()
    {
        $this->logState();

        return $this->to('example@example.com')->subject('example')->html('example');
    }
}
