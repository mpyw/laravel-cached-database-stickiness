<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(): array
    {
        return [MailChannel::class];
    }

    public function toMail(): MailMessage
    {
        return new MailMessage();
    }
}
