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
        return $this->shouldAssumeJobCallerModified($job->payload());
    }

    /**
     * @param  array     $payload
     * @return null|bool
     */
    protected function shouldAssumeJobCallerModified(array $payload): ?bool
    {
        $job = Str::parseCallback((string)filter_var($payload['job'] ?? ''))[0];

        if (null !== $result = $this->detectInterface($job)) {
            return $result;
        }

        return is_a($job, CallQueuedHandler::class, true)
            ? $this->shouldAssumeJobModified($payload)
            : null;
    }

    /**
     * @param  array     $payload
     * @return null|bool
     */
    protected function shouldAssumeJobModified(array $payload): ?bool
    {
        $commandName = Str::parseCallback((string)filter_var($payload['data']['commandName'] ?? ''))[0];

        if (null !== $result = $this->detectInterface($commandName)) {
            return $result;
        }

        if (is_a($commandName, CallQueuedListener::class, true)) {
            return $this->detectInterface($this->unserializePayload($payload)->class ?? null);
        }
        if (is_a($commandName, SendQueuedNotifications::class, true)) {
            return $this->detectInterface($this->unserializePayload($payload)->notification ?? null);
        }
        if (is_a($commandName, SendQueuedMailable::class, true)) {
            return $this->detectInterface($this->unserializePayload($payload)->mailable ?? null);
        }

        return null;
    }

    /**
     * @param  mixed     $class
     * @return null|bool
     */
    protected function detectInterface($class): ?bool
    {
        if (!is_object($class) && !is_string($class)) {
            return null;
        }
        if (is_subclass_of($class, ShouldAssumeModified::class)) {
            return true;
        }
        if (is_subclass_of($class, ShouldAssumeFresh::class)) {
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
