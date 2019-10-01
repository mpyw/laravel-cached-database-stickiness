<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\JobInitializers;

use Illuminate\Queue\Events\JobProcessing;

/**
 * Interface JobInitializerInterface
 */
interface JobInitializerInterface
{
    /**
     * Initialize database stickiness state before processing each job.
     *
     * @param \Illuminate\Queue\Events\JobProcessing $event
     */
    public function initializeStickinessState(JobProcessing $event): void;
}
