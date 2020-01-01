<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;

class ModifiedMailable extends Mailable implements ShouldQueue, ShouldAssumeModified
{
    public function build()
    {
        return $this->to('example@example.com')->subject('example')->html('example');
    }
}
