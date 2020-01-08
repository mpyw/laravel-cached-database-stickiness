<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class GeneralMailable extends Mailable implements ShouldQueue
{
    use LogsConnectionState;

    public function build()
    {
        $this->logState();

        return $this->to('example@example.com')->subject('example')->html('example');
    }
}
