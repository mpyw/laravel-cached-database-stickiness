<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature;

use Illuminate\Contracts\Mail\Factory as MailerFactory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mpyw\LaravelCachedDatabaseStickiness\ConnectionServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers\FreshCaller;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers\GeneralCaller;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\JobCallers\ModifiedCaller;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\FreshJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\GeneralJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\ModifiedJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\SideEffectFreshJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners\FreshListener;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners\GeneralListener;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Listeners\ModifiedListener;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables\FreshMailable;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables\GeneralMailable;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Mailables\ModifiedMailable;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications\FreshNotification;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications\GeneralNotification;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Notifications\ModifiedNotification;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class InitializingTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            StickinessServiceProvider::class,
            ConnectionServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'read' => [
                'database' => ':memory:',
            ],
            'write' => [],
            'sticky' => true,
        ]);
        $app['request'] = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.0.1']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        FreshCaller::enableStateLogging(true);
        GeneralCaller::enableStateLogging(true);
        ModifiedCaller::enableStateLogging(true);

        FreshJob::enableStateLogging(true);
        GeneralJob::enableStateLogging(true);
        ModifiedJob::enableStateLogging(true);
        SideEffectFreshJob::enableStateLogging(true);

        FreshListener::enableStateLogging(true);
        GeneralListener::enableStateLogging(true);
        ModifiedListener::enableStateLogging(true);

        FreshMailable::enableStateLogging(true);
        GeneralMailable::enableStateLogging(true);
        ModifiedMailable::enableStateLogging(true);

        FreshNotification::enableStateLogging(true);
        GeneralNotification::enableStateLogging(true);
        ModifiedNotification::enableStateLogging(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        FreshCaller::enableStateLogging(false);
        GeneralCaller::enableStateLogging(false);
        ModifiedCaller::enableStateLogging(false);

        FreshJob::enableStateLogging(false);
        GeneralJob::enableStateLogging(false);
        ModifiedJob::enableStateLogging(false);
        SideEffectFreshJob::enableStateLogging(false);

        FreshListener::enableStateLogging(false);
        GeneralListener::enableStateLogging(false);
        ModifiedListener::enableStateLogging(false);

        FreshMailable::enableStateLogging(false);
        GeneralMailable::enableStateLogging(false);
        ModifiedMailable::enableStateLogging(false);

        FreshNotification::enableStateLogging(false);
        GeneralNotification::enableStateLogging(false);
        ModifiedNotification::enableStateLogging(false);
    }

    protected function getRecordsModifiedViaReflection()
    {
        /* @var \Illuminate\Database\Connection $connection */
        $connection = DB::connection();

        /* @noinspection PhpUnhandledExceptionInspection */
        $property = new ReflectionProperty($connection, 'recordsModified');
        $property->setAccessible(true);
        return $property->getValue($connection);
    }

    public function testInitializationForJobs(): void
    {
        DB::connection();

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Bus::dispatch(new GeneralJob());
        GeneralJob::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Bus::dispatch(new FreshJob());
        FreshJob::assertLoggedState(false);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Bus::dispatch(new ModifiedJob());
        ModifiedJob::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());
    }

    public function testInitializationForListeners(): void
    {
        Event::listen('event:general', GeneralListener::class);
        Event::listen('event:fresh', FreshListener::class);
        Event::listen('event:modified', ModifiedListener::class);

        DB::connection();

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Event::dispatch('event:general');
        GeneralListener::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Event::dispatch('event:fresh');
        FreshListener::assertLoggedState(false);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Event::dispatch('event:modified');
        ModifiedListener::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());
    }

    public function testInitializationForNotifications(): void
    {
        $this->mock(Mailer::class)->shouldReceive('send');

        // [7.x] Multiple Mailers Per App
        if (interface_exists(MailerFactory::class)) {
            $this->mock(MailerFactory::class)
                ->shouldReceive('mailer')
                ->andReturn($this->app->make(Mailer::class));
        }

        DB::connection();

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Notification::send(collect([new AnonymousNotifiable()]), new GeneralNotification());
        GeneralNotification::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Notification::send(collect([new AnonymousNotifiable()]), new FreshNotification());
        FreshNotification::assertLoggedState(false);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Notification::send(collect([new AnonymousNotifiable()]), new ModifiedNotification());
        ModifiedNotification::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());
    }

    public function testInitializationForMailables(): void
    {
        $this->mock(Mailer::class)->shouldReceive('send');

        // [7.x] Multiple Mailers Per App
        if (interface_exists(MailerFactory::class)) {
            $this->mock(MailerFactory::class)
                ->shouldReceive('mailer')
                ->andReturn($this->app->make(Mailer::class));
        }

        DB::connection();

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Mail::send(new GeneralMailable());
        GeneralMailable::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Mail::send(new FreshMailable());
        FreshMailable::assertLoggedState(false);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Mail::send(new ModifiedMailable());
        ModifiedMailable::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());
    }

    public function testUnresolvedConnectionShouldBeInitializedAfterJobProcessingDispatched(): void
    {
        Bus::dispatch(new GeneralJob());
        GeneralJob::assertLoggedState(true);

        $this->assertFalse($this->getRecordsModifiedViaReflection());
    }

    public function testFreshJobWithSideEffectExcludedFromStateRevoking(): void
    {
        Bus::dispatch(new SideEffectFreshJob());
        SideEffectFreshJob::assertLoggedState(true);

        $this->assertTrue($this->getRecordsModifiedViaReflection());
    }
}
