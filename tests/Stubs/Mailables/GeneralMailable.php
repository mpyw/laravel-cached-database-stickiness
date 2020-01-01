<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class GeneralMailable extends Mailable implements ShouldQueue
{
    public function build()
    {
        return $this->to('example@example.com')->subject('example')->html('example');
    }
}
