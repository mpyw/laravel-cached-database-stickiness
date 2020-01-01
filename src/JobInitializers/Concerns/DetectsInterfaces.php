<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\Concerns;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Support\Str;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeFresh;
use Mpyw\LaravelCachedDatabaseStickiness\ShouldAssumeModified;
use Throwable;

trait DetectsInterfaces
{
    /**
     * Determine if the job should be run in fresh state.
     *
     * @param  \Illuminate\Contracts\Queue\Job $job
     * @return null|bool
     */
    public function shouldAssumeFresh(Job $job): ?bool
    {
        $result = $this->shouldAssumeModified($job);

        return is_bool($result) ? !$result : null;
    }

    /**
     * Determine if the job should be run in modified state.
     *
     * @param  \Illuminate\Contracts\Queue\Job $job
     * @return null|bool
     */
    public function shouldAssumeModified(Job $job): ?bool
    {
        $payload = $job->payload();

        $jobHandlerClass = Str::parseCallback((string)filter_var($payload['job'] ?? ''))[0];

        if (null !== $result = $this->detectInterface($jobHandlerClass)) {
            return $result;
        }

        return is_a($jobHandlerClass, CallQueuedHandler::class, true)
            ? $this->shouldAssumeCallQueuedHandlerAsModified($payload)
            : null;
    }

    /**
     * @param  array     $payload
     * @return null|bool
     */
    protected function shouldAssumeCallQueuedHandlerAsModified(array $payload): ?bool
    {
        $callQueuedHandlerCommandClass = Str::parseCallback((string)filter_var($payload['data']['commandName'] ?? ''))[0];

        if (null !== $result = $this->detectInterface($callQueuedHandlerCommandClass)) {
            return $result;
        }

        return $this->shouldAssumeCallQueuedHandlerCommandPayloadAsModified($callQueuedHandlerCommandClass, $payload);
    }

    /**
     * @param  string    $callQueuedHandlerCommandClass
     * @param  array     $payload
     * @return null|bool
     */
    protected function shouldAssumeCallQueuedHandlerCommandPayloadAsModified(string $callQueuedHandlerCommandClass, array $payload): ?bool
    {
        if (is_a($callQueuedHandlerCommandClass, CallQueuedListener::class, true)) {
            return $this->detectInterface($this->unserializePayload($payload)->class ?? null);
        }
        if (is_a($callQueuedHandlerCommandClass, SendQueuedNotifications::class, true)) {
            return $this->detectInterface($this->unserializePayload($payload)->notification ?? null);
        }
        if (is_a($callQueuedHandlerCommandClass, SendQueuedMailable::class, true)) {
            return $this->detectInterface($this->unserializePayload($payload)->mailable ?? null);
        }

        return null;
    }

    /**
     * @param  mixed     $command
     * @return null|bool
     */
    protected function detectInterface($command): ?bool
    {
        if (!is_object($command) && !is_string($command)) {
            return null;
        }
        if (is_subclass_of($command, ShouldAssumeModified::class)) {
            return true;
        }
        if (is_subclass_of($command, ShouldAssumeFresh::class)) {
            return false;
        }
        return null;
    }

    /**
     * @param  array $payload
     * @return mixed
     */
    protected function unserializePayload(array $payload)
    {
        if (!is_string($payload['data']['command'] ?? null)) {
            return null;
        }

        try {
            return unserialize($payload['data']['command']);
        } catch (Throwable $_) {
            return null;
        }
    }
}
