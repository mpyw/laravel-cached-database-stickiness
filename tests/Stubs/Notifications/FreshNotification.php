<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Concerns\LogsConnectionState;

class FreshNotification extends Notification implements ShouldQueue, ShouldAssumeFresh
{
    use Queueable, LogsConnectionState;

    public function via(): array
    {
        return [MailChannel::class];
    }

    public function toMail(): MailMessage
    {
        $this->logState();

        return new MailMessage();
    }
}
