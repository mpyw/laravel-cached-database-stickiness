<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class ModifiedMailable extends Mailable implements ShouldQueue, ShouldAssumeModified
{
    use LogsConnectionState;

    public function build()
    {
        $this->logState();

        return $this->to('example@example.com')->subject('example')->html('example');
    }
}
