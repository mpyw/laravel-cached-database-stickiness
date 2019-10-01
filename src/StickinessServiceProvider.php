<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Illuminate\Contracts\Events\Dispatcher;
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
     * @param \Illuminate\Contracts\Events\Dispatcher                 $events
     * @param \Mpyw\LaravelCachedDatabaseStickiness\StickinessManager $stickiness
     */
    public function boot(Dispatcher $events, StickinessManager $stickiness): void
    {
        $events->listen(ConnectionCreated::class, [$stickiness, 'onConnectionCreated']);
        $events->listen(JobProcessing::class, [$stickiness, 'onJobProcessing']);
        $events->listen(RecordsHaveBeenModified::class, [$stickiness, 'onRecordsHaveBeenModified']);
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->singleton(StickinessManager::class);
        $this->app->singleton('db.factory', ConnectionFactory::class);

        $this->app->bind(StickinessResolverInterface::class, IpBasedResolver::class);
        $this->app->bind(JobInitializerInterface::class, AlwaysModifiedInitializer::class);
    }
}
