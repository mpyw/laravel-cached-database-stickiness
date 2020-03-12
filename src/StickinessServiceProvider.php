<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\Events\ConnectionCreated;
use Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\AlwaysModifiedInitializer;
use Mpyw\LaravelCachedDatabaseStickiness\JobInitializers\JobInitializerInterface;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\IpBasedResolver;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessResolvers\StickinessResolverInterface;

class StickinessServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     *
     * @param \Illuminate\Contracts\Events\Dispatcher                       $events
     * @param \Mpyw\LaravelCachedDatabaseStickiness\StickinessEventListener $listener
     */
    public function boot(Dispatcher $events, StickinessEventListener $listener): void
    {
        $events->listen(JobProcessing::class, [$listener, 'onJobProcessing']);
        $events->listen(JobProcessed::class, [$listener, 'onJobProcessed']);
        $events->listen(JobExceptionOccurred::class, [$listener, 'onJobExceptionOccurred']);
        $events->listen(JobFailed::class, [$listener, 'onJobFailed']);
        $events->listen(ConnectionCreated::class, [$listener, 'onConnectionCreated']);
        $events->listen(RecordsHaveBeenModified::class, [$listener, 'onRecordsHaveBeenModified']);
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->singleton(StickinessManager::class);
        $this->app->singleton(StickinessEventListener::class);
        $this->app->singleton('db.factory', ConnectionFactory::class);

        $this->app->bindIf(StickinessResolverInterface::class, IpBasedResolver::class);
        $this->app->bindIf(JobInitializerInterface::class, AlwaysModifiedInitializer::class);
    }
}
