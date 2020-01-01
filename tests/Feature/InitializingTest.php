<?php

namespace Mpyw\LaravelCachedDatabaseStickiness\Tests\Feature;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mpyw\LaravelCachedDatabaseStickiness\ConnectionServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\StickinessServiceProvider;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\FreshJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\GeneralJob;
use Mpyw\LaravelCachedDatabaseStickiness\Tests\Stubs\Jobs\ModifiedJob;
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

        $this->assertTrue($this->getRecordsModifiedViaReflection());

        Bus::dispatch(new FreshJob());

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Bus::dispatch(new ModifiedJob());

        $this->assertTrue($this->getRecordsModifiedViaReflection());
    }

    public function testInitializationForNotifications(): void
    {
        $this->mock(Mailer::class)->shouldReceive('send');

        DB::connection();

        $general = Mockery::mock(GeneralNotification::class . '[via]');
        $general->shouldReceive('via')->andReturn([MailChannel::class]);
        $general->shouldReceive('toMail')->andReturn(new MailMessage());

        $fresh = Mockery::mock(FreshNotification::class . '[via]');
        $fresh->shouldReceive('via')->andReturn([MailChannel::class]);
        $fresh->shouldReceive('toMail')->andReturn(new MailMessage());

        $modified = Mockery::mock(ModifiedNotification::class . '[via]');
        $modified->shouldReceive('via')->andReturn([MailChannel::class]);
        $modified->shouldReceive('toMail')->andReturn(new MailMessage());

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Notification::send(collect([new AnonymousNotifiable()]), $general);

        $this->assertTrue($this->getRecordsModifiedViaReflection());

        Notification::send(collect([new AnonymousNotifiable()]), $fresh);

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Notification::send(collect([new AnonymousNotifiable()]), $modified);

        $this->assertTrue($this->getRecordsModifiedViaReflection());
    }

    public function testInitializationForMailables(): void
    {
        $this->mock(Mailer::class)->shouldReceive('send');

        DB::connection();

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Mail::send(new GeneralMailable());

        $this->assertTrue($this->getRecordsModifiedViaReflection());

        Mail::send(new FreshMailable());

        $this->assertFalse($this->getRecordsModifiedViaReflection());

        Mail::send(new ModifiedMailable());

        $this->assertTrue($this->getRecordsModifiedViaReflection());
    }
}
